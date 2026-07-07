<?php

declare(strict_types=1);

namespace App\Infrastructure\Email;

final class WelcomeEmailTemplate
{
    public static function render(string $name, string $dashboardUrl): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Bem-vindo ao MayLove</title>
        </head>
        <body style="margin:0;padding:0;background:#f8f4f6;font-family:Arial,Helvetica,sans-serif;color:#2d1b24;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px;">
                <tr>
                    <td align="center">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;padding:32px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#b44d72;">MayLove</p>
                                    <h1 style="margin:0 0 16px;font-size:28px;">Bem-vindo, {$safeName}!</h1>
                                    <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">Seu e-mail foi confirmado. Agora você pode criar homenagens digitais e compartilhar com quem você ama.</p>
                                    <p style="margin:0;text-align:center;">
                                        <a href="{$safeUrl}" style="display:inline-block;background:#d94f7a;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:999px;font-weight:bold;">Ir para o MayLove</a>
                                    </p>
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
