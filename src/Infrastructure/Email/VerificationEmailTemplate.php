<?php

declare(strict_types=1);

namespace App\Infrastructure\Email;

final class VerificationEmailTemplate
{
    public static function render(string $name, string $verificationUrl, int $validityHours): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $validityText = $validityHours === 1 ? '1 hora' : $validityHours.' horas';
        $brand = BrandColors::NAME;
        $accent = BrandColors::PRIMARY_STRONG;
        $primary = BrandColors::PRIMARY;
        $bg = BrandColors::BG;
        $text = BrandColors::TEXT;
        $surface = BrandColors::SURFACE;
        $muted = BrandColors::MUTED;

        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Confirme seu e-mail — {$brand}</title>
        </head>
        <body style="margin:0;padding:0;background:{$bg};font-family:Arial,Helvetica,sans-serif;color:{$text};">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px;">
                <tr>
                    <td align="center">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:{$surface};border-radius:16px;padding:32px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:{$accent};">{$brand}</p>
                                    <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;">Confirme seu e-mail</h1>
                                    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">Olá, {$safeName}! Finalize seu cadastro para criar homenagens digitais no {$brand}.</p>
                                    <p style="margin:0 0 24px;text-align:center;">
                                        <a href="{$safeUrl}" style="display:inline-block;background:{$primary};color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:999px;font-weight:bold;">Confirmar e-mail</a>
                                    </p>
                                    <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:{$muted};">Este link expira em {$validityText}.</p>
                                    <p style="margin:0;font-size:13px;line-height:1.6;color:{$muted};word-break:break-all;">Se o botão não funcionar, copie e cole este link no navegador:<br>{$safeUrl}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }
}
