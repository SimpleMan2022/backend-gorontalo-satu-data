<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Gorontalo Satu Data Makro</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f9fc;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }

        .email-wrapper {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container {
            margin-bottom: 20px;
        }

        .logo-image {
            max-width: 200px;
            height: auto;
        }

        .divider {
            height: 2px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }

        h1 {
            color: #833ED4;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }

        p {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 30px;
            text-align: center;
        }

        .reset-button {
            display: block;
            background-color: #833ED4;
            color: white;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            margin: 0 auto;
            max-width: 200px;
            transition: background-color 0.3s ease;
        }

        .reset-button:hover {
            background-color: #A855F7;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #6b7280;
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
                margin: 20px auto;
            }

            .email-wrapper {
                padding: 20px;
            }

            .logo-image {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="email-wrapper">
            <div class="header">
                <div class="logo-container">
                    <img src="https://res.cloudinary.com/destjdrbz/image/upload/v1730953682/gsd/logo-gsd_cdni7o.png" alt="Gorontalo Satu Data Makro Logo" class="logo-image">
                </div>
                <div class="divider"></div>
            </div>
            <h1>Gorontalo Satu Data</h1>
            <p>Untuk melanjutkan proses reset password akun Gorontalo Satu Data Makro Anda, silakan klik tombol di bawah ini:</p>
            <a href="36.67.194.180/auth/reset-password?token={{ $token->token }}" class="reset-button">Reset Password</a>
            <div class="footer">
                <p>Jika Anda tidak meminta reset password, Anda dapat mengabaikan email ini.</p>
                <p>&copy; 2024 Gorontalo Satu Data Makro. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
