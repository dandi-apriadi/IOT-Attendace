<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PresenSync</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/premium-design.css') }}">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(rgba(0, 30, 64, 0.7), rgba(0, 30, 64, 0.7)), url('{{ asset("images/background.png") }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
            font-family: 'Inter', sans-serif;
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
            width: min(260px, 82%);
            height: auto;
            margin: 0 auto 1.25rem;
            display: block;
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
            background: rgba(255, 255, 255, 0.2);
            border: none;
            padding: 1rem;
            border-radius: var(--radius-md);
            color: #fff;
            font-family: inherit;
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .form-control:focus {
            outline: 2px solid var(--kinetic-yellow);
            background: #fff;
            color: var(--text-primary);
        }
        .login-btn {
            width: 100%;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <img class="login-logo" src="{{ asset('images/presensync-logo-card.png') }}" alt="PresenSync">
        <p style="opacity: 0.72; margin-bottom: 3rem;">Smart Attendance Sync Platform</p>
        
        @if ($errors->any())
            <div style="background: rgba(186, 26, 26, 0.2); border: 1px solid rgba(186, 26, 26, 0.5); padding: 0.75rem; border-radius: 10px; margin-bottom: 1rem; text-align: left; font-size: 0.9rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.attempt') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Email / User ID</label>
                <input type="email" name="email" class="form-control" placeholder="admin@poltek.ac.id" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-kinetic login-btn">MASUK SEKARANG</button>
        </form>
        
        <p style="margin-top: 2rem; font-size: 0.8rem; opacity: 0.5;">PresenSync by Politeknik Negeri Manado</p>
    </div>
</body>
</html>
