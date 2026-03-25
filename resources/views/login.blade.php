<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IoT Attendance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/premium-design.css') }}">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--primary-blue);
            padding: 20px;
        }
        .login-card {
            max-width: 440px;
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-xl);
            padding: 3rem;
            color: #fff;
            text-align: center;
        }
        .login-logo {
            font-size: 3rem;
            color: var(--kinetic-yellow);
            margin-bottom: 1.5rem;
        }
        .form-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.5rem;
            opacity: 0.7;
        }
        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            padding: 1rem;
            border-radius: var(--radius-md);
            color: #fff;
            font-family: inherit;
        }
        .form-control:focus {
            outline: 2px solid var(--kinetic-yellow);
        }
        .login-btn {
            width: 100%;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo"><i class="fas fa-microchip"></i></div>
        <h2 class="display-font" style="margin-bottom: 0.5rem;">Tech-Presence</h2>
        <p style="opacity: 0.6; margin-bottom: 3rem;">Politeknik Negeri Manado IoT Gateway</p>
        
        <form action="{{ route('dashboard') }}" method="GET">
            <div class="form-group">
                <label class="form-label">Email / User ID</label>
                <input type="text" class="form-control" placeholder="admin@poltek.ac.id">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" placeholder="••••••••">
            </div>
            <button type="submit" class="btn-kinetic login-btn">MASUK SEKARANG</button>
        </form>
        
        <p style="margin-top: 2rem; font-size: 0.8rem; opacity: 0.5;">Design by dandi-apriadi</p>
    </div>
</body>
</html>
