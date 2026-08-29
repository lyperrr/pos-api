<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $resetUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔑 Reset Kata Sandi Akun NIRA POS',
        );
    }

    public function content(): Content
    {
        $year = date('Y');
        
        // Use ultra-compact 2.4KB logo_nira_icon.png (80x80px) for 100% crisp, fast rendering in Gmail
        $logoIconPath = base_path('../pos-app/public/logo_nira_icon.png');
        $logoSmallPath = base_path('../pos-app/public/logo_nira_small.png');
        $logoPath = file_exists($logoIconPath) ? $logoIconPath : $logoSmallPath;
        
        $logoImgTag = '';
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
            $logoImgTag = "<img src='data:image/png;base64,{$logoBase64}' alt='NIRA Logo' width='36' height='36' style='display: block; width: 36px; height: 36px; border: 0; outline: none; text-decoration: none;' />";
        } else {
            $logoImgTag = "<div style='width: 36px; height: 36px; background-color: #5ea211; border-radius: 10px; text-align: center; line-height: 36px; color: #ffffff; font-weight: 900; font-size: 15px;'>NR</div>";
        }

        // Top centered header with real NIRA logo image next to NIRA POS text
        $logoHeaderHtml = "
        <table role='presentation' border='0' cellspacing='0' cellpadding='0' style='margin: 0 auto;'>
            <tr>
                <td style='vertical-align: middle;'>
                    {$logoImgTag}
                </td>
                <td style='vertical-align: middle; padding-left: 10px;'>
                    <span style='color: #0f172a; font-size: 22px; font-weight: 900; font-family: -apple-system, sans-serif; letter-spacing: -0.5px;'>NIRA <span style='color: #5ea211;'>POS</span></span>
                </td>
            </tr>
        </table>";

        $htmlContent = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Reset Kata Sandi NIRA POS</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: #0f172a; -webkit-font-smoothing: antialiased;'>
            <table role='presentation' width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f1f5f9; padding: 48px 16px;'>
                <tr>
                    <td align='center'>
                        
                        <!-- 1. TOP LOGO IMAGE HEADER + NIRA POS TEXT (OUTSIDE CARD) -->
                        <div style='margin-bottom: 28px; text-align: center;'>
                            {$logoHeaderHtml}
                        </div>

                        <!-- 2. MAIN WHITE CARD CONTAINER -->
                        <table role='presentation' width='100%' border='0' cellspacing='0' cellpadding='0' style='max-width: 500px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0;'>
                            <tr>
                                <td style='padding: 40px 36px; text-align: center;'>
                                    
                                    <!-- SLEEK SECURITY KEY BADGE -->
                                    <div style='margin: 0 auto 24px auto; width: 64px; height: 64px; background-color: #f0fdf4; border-radius: 20px; border: 1px solid #dcfce7; text-align: center; line-height: 64px;'>
                                        <span style='font-size: 30px; display: inline-block; vertical-align: middle;'>🔑</span>
                                    </div>

                                    <!-- CENTERED HEADING -->
                                    <h1 style='margin: 0 0 12px 0; font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px;'>
                                        Lupa kata sandi Anda?
                                    </h1>

                                    <!-- CENTERED DESCRIPTION -->
                                    <p style='margin: 0 0 28px 0; font-size: 14px; line-height: 1.6; color: #64748b; font-weight: 400; max-width: 400px; margin-left: auto; margin-right: auto;'>
                                        Halo <strong>{$this->user->full_name}</strong>, Anda menerima email ini karena ada permintaan untuk mengatur ulang kata sandi akun NIRA POS Anda.
                                    </p>

                                    <!-- CENTERED GREEN ACTION BUTTON -->
                                    <div style='margin: 0 0 28px 0; text-align: center;'>
                                        <a href='{$this->resetUrl}' target='_blank' style='background-color: #5ea211; color: #ffffff; font-size: 15px; font-weight: 800; padding: 14px 32px; text-decoration: none; border-radius: 14px; display: inline-block; width: 85%; max-width: 320px; box-shadow: 0 6px 16px rgba(94, 162, 17, 0.35); border: 1px solid #528f0e;'>
                                            Reset Kata Sandi Saya
                                        </a>
                                    </div>

                                    <!-- CENTERED EXPIRATION NOTE -->
                                    <p style='margin: 0 0 24px 0; font-size: 13px; line-height: 1.5; color: #94a3b8; max-width: 400px; margin-left: auto; margin-right: auto;'>
                                        Tautan ini berlaku selama <strong>15 menit</strong>. Jika Anda tidak merasa meminta reset kata sandi, silakan abaikan email ini.
                                    </p>

                                    <!-- FALLBACK LINK -->
                                    <div style='padding-top: 20px; border-top: 1px solid #f1f5f9; text-align: center;'>
                                        <p style='margin: 0; font-size: 11px; color: #cbd5e1; line-height: 1.5;'>
                                            Atau tempel tautan berikut di browser Anda:<br>
                                            <a href='{$this->resetUrl}' style='color: #5ea211; font-weight: 700; word-break: break-all; text-decoration: underline;'>{$this->resetUrl}</a>
                                        </p>
                                    </div>

                                </td>
                            </tr>
                        </table>

                        <!-- MINIMAL CLEAN COPYRIGHT -->
                        <div style='margin-top: 24px; text-align: center; color: #94a3b8; font-size: 12px;'>
                            Hak Cipta &copy; {$year} NIRA POS. Seluruh hak cipta dilindungi.
                        </div>

                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        return new Content(htmlString: $htmlContent);
    }
}
