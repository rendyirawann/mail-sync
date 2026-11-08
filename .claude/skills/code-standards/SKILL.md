---
name: code-standards
description: Use when writing or reviewing PHP/Blade/JS code in Mail Sync to apply the project's naming, architecture, formatting, and PHPDoc conventions (Bahasa Indonesia docs, Pint, Larastan level 5, Conventional Commits).
---

# Code Standards (Mail Sync)

## Langkah

1. Tentukan layer yang benar (lihat `docs/ARCHITECTURE.md`): Controller → FormRequest → Service/Action → Model.
2. Tulis kode dengan type hint parameter & return, constructor property promotion, `readonly` bila cocok, `match`, early return.
3. PHPDoc Bahasa Indonesia untuk class & method public (`@param` dengan array shape, `@return`, `@throws`). Komentar menjelaskan *mengapa*.
4. Format & analisis: `vendor/bin/pint --dirty`, `vendor/bin/phpstan analyse` (config `phpstan.neon`, level 5), `npm run lint` untuk `resources/js`.
5. Commit: `feat:`, `fix:`, `refactor:`, `docs:`, `style:`, `test:`, `chore:`.

## Benar vs salah

```php
// SALAH — validasi & query di controller, tanpa scope pemilik
public function update(Request $request, $id)
{
    $account = MailAccount::findOrFail($id);
    $account->update($request->all());
}

// BENAR
/**
 * Perbarui akun email milik user yang login.
 */
public function update(UpdateMailAccountRequest $request, MailAccount $mailAccount, MailAccountService $service): RedirectResponse
{
    $this->authorize('update', $mailAccount);
    $service->update($mailAccount, $request->validated());

    return to_route('mail-accounts.index')->with('success', 'Akun email diperbarui.');
}
```

Contoh nyata di project: `app/Http/Controllers/Controller.php` (`ownAccountOrFail`), `app/Http/Middleware/ProtectAgainstSpam.php` (PHPDoc + konstanta), `app/Http/Controllers/Frontend/SeoController.php`.

## Penamaan

| Item | Konvensi | Contoh |
| --- | --- | --- |
| Model | Singular PascalCase | `MailAccount` |
| Tabel | Plural snake_case | `mail_accounts`, pivot `mail_account_user` |
| Request | `Store…Request` / `Update…Request` | `StoreMailAccountRequest` |
| URL | kebab-case | `/admin/mail-accounts` |
| Route name | titik | `mail-accounts.index` |
| Boolean | `is_` / `has_` | `is_active` |

## Checklist

- [ ] Pint & PHPStan lulus, tidak ada `console.log`
- [ ] Tidak ada `env()` di luar `config/`
- [ ] Tidak ada logika bisnis/query di Blade; UI berulang jadi komponen di `resources/views/components`

## Hindari

- Refactor besar kode lama tanpa alasan keamanan/bug; `@phpstan-ignore` untuk menutupi error; facade alias global (`use DB;`) di kode baru — pakai `Illuminate\Support\Facades\DB`.
