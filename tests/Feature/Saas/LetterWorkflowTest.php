<?php

namespace Tests\Feature\Saas;

use App\Enums\DispositionStatus;
use App\Enums\IncomingStatus;
use App\Enums\OutgoingStatus;
use App\Enums\TeamRole;
use App\Models\Disposition;
use App\Models\Employee;
use App\Models\IncomingLetter;
use App\Models\LetterClassification;
use App\Models\LetterTemplate;
use App\Models\OutgoingLetter;
use App\Support\Tenancy\CurrentTeam;
use Database\Seeders\DefaultLetterTemplateSeeder;

/**
 * Alur surat masuk → disposisi → selesai, dan surat keluar → persetujuan → nomor + QR → PDF.
 */
class LetterWorkflowTest extends SaasTestCase
{
    public function test_incoming_letter_gets_sequential_agenda_number(): void
    {
        $team = $this->makeTeam();
        $owner = $this->member($team);

        foreach (['Pertama', 'Kedua'] as $subject) {
            $this->actingAs($owner)->post(route('team.incoming.store', $team), [
                'letter_number' => '005/'.$subject, 'letter_date' => now()->toDateString(), 'received_date' => now()->toDateString(),
                'sender' => 'Dinas Lain', 'subject' => $subject, 'nature' => 'segera',
            ])->assertRedirect();
        }

        app(CurrentTeam::class)->set($team);
        $this->assertSame([1, 2], IncomingLetter::orderBy('agenda_number')->pluck('agenda_number')->all());
    }

    public function test_incoming_validation_rejects_non_https_attachment(): void
    {
        $team = $this->makeTeam();

        $this->actingAs($this->member($team))->post(route('team.incoming.store', $team), [
            'letter_number' => '1', 'letter_date' => now()->toDateString(), 'received_date' => now()->toDateString(),
            'sender' => 'X', 'subject' => 'Y', 'nature' => 'biasa', 'attachment_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('attachment_url');
    }

    public function test_disposition_flow_updates_letter_status_and_notifies_recipient(): void
    {
        $team = $this->makeTeam();
        $owner = $this->member($team);
        $staff = $this->member($team, TeamRole::Staf);

        app(CurrentTeam::class)->set($team);
        $employee = Employee::create(['name' => 'Andi', 'user_id' => $staff->id]);
        $letter = IncomingLetter::create([
            'agenda_number' => 1, 'agenda_year' => 2026, 'letter_number' => '1', 'letter_date' => now(), 'received_date' => now(),
            'sender' => 'A', 'subject' => 'Undangan', 'nature' => 'biasa', 'status' => IncomingStatus::Baru,
        ]);
        app(CurrentTeam::class)->set(null);

        $this->actingAs($owner)->post(route('team.dispositions.store', [$team, $letter]), [
            'to_employee_ids' => [$employee->id], 'instruction' => 'Untuk dihadiri',
        ])->assertRedirect();

        $this->assertSame(IncomingStatus::Didisposisi, $letter->fresh()->status);
        $this->assertSame(1, $staff->notifications()->count());

        $disposition = Disposition::withoutGlobalScopes()->firstOrFail();

        // Selesai wajib disertai laporan.
        $this->actingAs($staff)->patch(route('team.dispositions.update', [$team, $disposition]), ['status' => 'selesai'])
            ->assertSessionHasErrors('response');

        $this->actingAs($staff)->patch(route('team.dispositions.update', [$team, $disposition]), ['status' => 'selesai', 'response' => 'Sudah hadir'])
            ->assertRedirect();

        $this->assertSame(DispositionStatus::Selesai, $disposition->fresh()->status);
        $this->assertSame(IncomingStatus::Selesai, $letter->fresh()->status);
    }

    public function test_outgoing_letter_number_and_qr_issued_on_approval(): void
    {
        $this->seed(DefaultLetterTemplateSeeder::class);
        $team = $this->makeTeam(attributes: ['profile' => ['numbering' => ['outgoing_format' => '{nomor}/{kode}/{bulan_romawi}/{tahun}', 'digits' => 3]]]);
        $owner = $this->member($team);
        $staff = $this->member($team, TeamRole::Staf);

        app(CurrentTeam::class)->set($team);
        $code = LetterClassification::create(['code' => '005', 'name' => 'Undangan']);
        app(CurrentTeam::class)->set(null);
        $template = LetterTemplate::withoutGlobalScopes()->where('name', 'Surat Pemberitahuan')->firstOrFail();

        $this->actingAs($staff)->post(route('team.outgoing.store', $team), [
            'template_id' => $template->id, 'recipient' => 'Camat', 'subject' => 'Pemberitahuan rapat',
            'letter_date' => '2026-11-10', 'nature' => 'biasa', 'classification_id' => $code->id,
            'content' => ['kota' => 'Lubuk Pakam', 'isi' => 'Isi pemberitahuan', 'tidak_ada' => 'diabaikan'],
            'next' => 'submit',
        ])->assertRedirect();

        $letter = OutgoingLetter::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(OutgoingStatus::Menunggu, $letter->status);
        $this->assertNull($letter->letter_number, 'Nomor belum terbit sebelum disetujui.');
        $this->assertArrayNotHasKey('tidak_ada', $letter->content);
        $this->assertSame(1, $owner->notifications()->count());

        // Staf tidak boleh menyetujui.
        $this->actingAs($staff)->post(route('team.outgoing.approve', [$team, $letter]))->assertForbidden();

        $this->actingAs($owner)->post(route('team.outgoing.approve', [$team, $letter]))->assertRedirect();
        $letter->refresh();

        $this->assertSame(OutgoingStatus::Disetujui, $letter->status);
        $this->assertSame('001/005/XI/2026', $letter->letter_number);
        $this->assertSame(32, strlen((string) $letter->verification_token));

        $this->get(route('letters.verify', $letter->verification_token))->assertOk()->assertSee('Surat terverifikasi')->assertSee('001/005/XI/2026');

        $pdf = $this->actingAs($owner)->get(route('team.outgoing.pdf', [$team, $letter]));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
    }

    public function test_unknown_verification_token_shows_not_found_message(): void
    {
        $this->get('/verifikasi/'.str_repeat('a', 32))->assertOk()->assertSee('Surat tidak ditemukan');
    }

    public function test_template_html_is_sanitized(): void
    {
        $team = $this->makeTeam();

        $this->actingAs($this->member($team))->post(route('team.templates.store', $team), [
            'name' => 'Uji', 'paper' => 'A4',
            'body' => '<p onclick="x()">Halo {{nama}}</p><script>alert(1)</script><img src=x onerror=alert(1)>',
            'fields' => [['key' => 'nama', 'label' => 'Nama', 'type' => 'text']],
        ])->assertRedirect();

        $template = LetterTemplate::withoutGlobalScopes()->where('name', 'Uji')->firstOrFail();
        $this->assertSame('<p>Halo {{nama}}</p>', $template->body);
        $this->assertSame($team->id, $template->team_id);
    }

    public function test_template_cannot_reuse_system_keys(): void
    {
        $team = $this->makeTeam();

        $this->actingAs($this->member($team))->post(route('team.templates.store', $team), [
            'name' => 'Uji', 'paper' => 'A4', 'body' => '<p>x</p>',
            'fields' => [['key' => 'nomor_surat', 'label' => 'Nomor', 'type' => 'text']],
        ])->assertSessionHasErrors('fields.0.key');
    }
}
