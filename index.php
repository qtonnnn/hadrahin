<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hadrah - Grup Seni Hadrah</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 50%, #2d1b4e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background Pattern */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: 
                repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255, 215, 0, 0.03) 35px, rgba(255, 215, 0, 0.03) 70px);
            animation: patternMove 20s linear infinite;
            z-index: 0;
        }

        @keyframes patternMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(70px, 70px); }
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.8), transparent);
            border-radius: 50%;
            animation: float linear infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) translateX(0) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) translateX(100px) scale(1);
                opacity: 0;
            }
        }

        /* Main Container */
        .container {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 40px;
            max-width: 600px;
        }

        /* Logo Container */
        .logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 40px;
        }

        .logo-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            animation: glow 3s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.5;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.2);
                opacity: 0.8;
            }
        }

        .logo {
            font-size: 120px;
            position: relative;
            z-index: 2;
            animation: logoEntrance 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            filter: drop-shadow(0 10px 30px rgba(255, 215, 0, 0.4));
        }

        @keyframes logoEntrance {
            0% {
                transform: scale(0) rotate(-180deg);
                opacity: 0;
            }
            60% {
                transform: scale(1.1) rotate(10deg);
            }
            100% {
                transform: scale(1) rotate(0);
                opacity: 1;
            }
        }

        /* Title Section */
        .title {
            font-size: 72px;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: titleFadeIn 1s ease-out 0.5s both;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        @keyframes titleFadeIn {
            0% {
                opacity: 0;
                transform: translateY(30px);
                filter: blur(10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        /* Subtitle */
        .subtitle {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 8px;
            text-transform: uppercase;
            margin-bottom: 50px;
            animation: subtitleFadeIn 1s ease-out 0.8s both;
            font-weight: 300;
        }

        @keyframes subtitleFadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Decorative Line */
        .divider {
            width: 120px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #ffd700, transparent);
            margin: 0 auto 50px;
            animation: dividerExpand 0.8s ease-out 1.2s both;
        }

        @keyframes dividerExpand {
            0% {
                width: 0;
                opacity: 0;
            }
            100% {
                width: 120px;
                opacity: 1;
            }
        }

        /* Button */
        .btn {
            display: inline-block;
            padding: 18px 60px;
            background: linear-gradient(135deg, #ffd700 0%, #ffb300 100%);
            color: #0a0e27;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 50px;
            box-shadow: 0 10px 40px rgba(255, 215, 0, 0.4);
            animation: buttonEntrance 0.8s ease-out 1.5s both;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 1px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 50px rgba(255, 215, 0, 0.6);
        }

        .btn:active {
            transform: translateY(-2px);
        }

        @keyframes buttonEntrance {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }
            60% {
                transform: translateY(-5px) scale(1.05);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Pulsing Effect on Button */
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 10px 40px rgba(255, 215, 0, 0.4);
            }
            50% {
                box-shadow: 0 10px 50px rgba(255, 215, 0, 0.7);
            }
        }

        .btn {
            animation: buttonEntrance 0.8s ease-out 1.5s both, pulse 2s ease-in-out 2.5s infinite;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .logo {
                font-size: 80px;
            }

            .title {
                font-size: 48px;
            }

            .subtitle {
                font-size: 16px;
                letter-spacing: 4px;
            }

            .btn {
                padding: 16px 40px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }

            .logo {
                font-size: 60px;
            }

            .title {
                font-size: 36px;
            }

            .subtitle {
                font-size: 14px;
                letter-spacing: 3px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>

    <!-- Floating Particles -->
    <div class="particles" id="particles"></div>

    <!-- Main Container -->
    <div class="container">
        <div class="logo-wrapper">
            <div class="logo-glow"></div>
            <img src="assets/img/logo.png" alt="Hadrah Logo" class="logo" style="width: 250px;height: 250px; object-fit: contain;">
        </div>

        <h1 class="title">Hadrahin</h1>
        <p class="subtitle">Managemen sistem Hadrah</p>
        <div class="divider"></div>
    </div>

    <script>
        // Generate Floating Particles
        const particlesContainer = document.getElementById('particles');
        const particleCount = 30;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            particle.style.animationDelay = Math.random() * 5 + 's';
            particlesContainer.appendChild(particle);
        }

        // Fade out effect after animations complete
        const container = document.querySelector('.container');
        
        // Start fade out after 5 seconds
        setTimeout(() => {
            container.style.transition = 'opacity 1s ease-out, transform 1s ease-out';
            container.style.opacity = '0';
            container.style.transform = 'scale(0.95)';
            
            // Auto redirect after fade out completes
            setTimeout(() => {
                window.location.href = 'auth/login.php';
            }, 1000);
        }, 5000);
    </script>
</body>
</html>