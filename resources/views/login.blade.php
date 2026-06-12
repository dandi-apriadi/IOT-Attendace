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
    <script>
        document.documentElement.classList.add('has-js');
    </script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            position: relative;
            isolation: isolate;
            overflow-x: hidden;
            background: oklch(18% 0.038 236);
            padding: 20px;
            font-family: 'Inter', sans-serif;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background:
                linear-gradient(135deg, rgba(8, 20, 42, 0.96), rgba(10, 48, 68, 0.88) 44%, rgba(20, 130, 104, 0.72)),
                linear-gradient(90deg, rgba(245, 250, 249, 0.08) 1px, transparent 1px),
                linear-gradient(0deg, rgba(245, 250, 249, 0.06) 1px, transparent 1px);
            background-size: auto, 72px 72px, 72px 72px;
        }
        .intro-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 50;
            align-items: center;
            justify-content: center;
            padding: clamp(18px, 4vw, 44px);
            overflow: hidden;
            background:
                linear-gradient(135deg, rgba(8, 20, 42, 0.92), rgba(10, 48, 68, 0.86) 48%, rgba(20, 130, 104, 0.78)),
                oklch(18% 0.038 236);
            transition: opacity 700ms cubic-bezier(0.22, 1, 0.36, 1), visibility 700ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        .has-js .intro-overlay {
            display: flex;
        }
        .intro-overlay.is-hidden {
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
        }
        .intro-stage {
            width: min(520px, 58vw, calc(44vh * 16 / 9));
            aspect-ratio: 16 / 9;
            position: relative;
            overflow: hidden;
            border-radius: 22px;
            background: oklch(98% 0.006 190);
            border: 1px solid rgba(230, 247, 244, 0.34);
            box-shadow: 0 26px 78px rgba(2, 10, 26, 0.38);
        }
        .intro-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(0.98) contrast(1.03);
            background: oklch(98% 0.006 190);
        }
        .intro-video::-webkit-media-controls,
        .intro-video::-webkit-media-controls-enclosure,
        .intro-video::-webkit-media-controls-panel {
            display: none !important;
        }
        .intro-skip {
            position: fixed;
            top: clamp(16px, 3vw, 28px);
            right: clamp(16px, 3vw, 28px);
            z-index: 51;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border: 1px solid rgba(8, 20, 42, 0.16);
            border-radius: 999px;
            background: rgba(8, 20, 42, 0.68);
            color: oklch(98% 0.006 190);
            cursor: pointer;
            backdrop-filter: blur(16px);
        }
        .intro-skip:focus-visible {
            outline: 3px solid rgba(18, 155, 120, 0.56);
            outline-offset: 3px;
        }
        .login-card {
            max-width: 440px;
            width: 100%;
            position: relative;
            background: oklch(97% 0.008 190);
            border: 1px solid rgba(230, 247, 244, 0.58);
            border-radius: var(--radius-xl);
            padding: 3rem;
            color: var(--text-primary);
            text-align: center;
            box-shadow: 0 30px 90px rgba(2, 10, 26, 0.28);
        }
        .login-logo {
            width: min(260px, 82%);
            height: auto;
            margin: 0 auto 1.25rem;
            display: block;
            filter: drop-shadow(0 14px 28px rgba(0, 8, 24, 0.16));
        }
        .form-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }
        .login-card .form-label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
        }
        .form-control {
            width: 100%;
            background: oklch(93% 0.01 235);
            border: 1px solid oklch(84% 0.014 225);
            padding: 1rem;
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-family: inherit;
        }
        .form-control::placeholder {
            color: oklch(64% 0.012 230);
        }
        .form-control:focus {
            outline: 2px solid var(--kinetic-yellow);
            background: oklch(98% 0.006 190);
            color: var(--text-primary);
        }
        .login-btn {
            width: 100%;
            margin-top: 1rem;
        }
        @media (prefers-reduced-motion: reduce) {
            .intro-overlay {
                display: none;
            }
        }
        @media (max-width: 640px) {
            .intro-overlay {
                padding: 18px;
            }
            .intro-stage {
                width: min(76vw, 320px, calc(30vh * 16 / 9));
                border-radius: 16px;
            }
            .intro-skip {
                width: 40px;
                height: 40px;
            }
        }
        @media (max-height: 560px) and (orientation: landscape) {
            .intro-stage {
                width: min(420px, 44vw, calc(40vh * 16 / 9));
                border-radius: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="intro-overlay" data-intro-overlay>
        <div class="intro-stage">
            <video class="intro-video" autoplay muted playsinline preload="auto" poster="{{ asset('images/presensync-video-poster.jpg') }}?v=clean-2" data-intro-video aria-hidden="true" disablepictureinpicture controlslist="nodownload nofullscreen noremoteplayback">
                <source src="{{ asset('videos/presensync-intro.webm') }}?v=clean-2" type="video/webm">
                <source src="{{ asset('videos/presensync-intro.mp4') }}?v=clean-2" type="video/mp4">
            </video>
        </div>
        <button class="intro-skip" type="button" data-intro-skip aria-label="Lewati intro">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="login-card">
        <img class="login-logo" src="{{ asset('images/presensync-logo-web.png') }}" alt="PresenSync">
        <p style="opacity: 0.72; margin-bottom: 3rem;">Smart Attendance Sync Platform</p>
        
        @if ($errors->any())
            <div style="background: rgba(186, 26, 26, 0.12); border: 1px solid rgba(186, 26, 26, 0.36); color: #BA1A1A; padding: 0.75rem; border-radius: 10px; margin-bottom: 1rem; text-align: left; font-size: 0.9rem;">
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

    <script>
        (() => {
            const overlay = document.querySelector('[data-intro-overlay]');
            const video = document.querySelector('[data-intro-video]');
            const skip = document.querySelector('[data-intro-skip]');
            const motionReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!overlay || !video || motionReduced) {
                overlay?.classList.add('is-hidden');
                return;
            }

            const finishIntro = () => {
                overlay.classList.add('is-hidden');
                video.pause();
            };

            video.addEventListener('ended', finishIntro, { once: true });
            video.addEventListener('error', finishIntro, { once: true });
            skip?.addEventListener('click', finishIntro);
            video.play?.().catch(() => {});
            window.setTimeout(finishIntro, 11000);
        })();
    </script>
</body>
</html>
