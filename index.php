<!DOCTYPE html>
<html>
<head>
    <title>Login Page</title>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        @keyframes glow {
            0% {
                box-shadow: 0 0 5px #00ff7f;
            }
            50% {
                box-shadow: 0 0 20px #00ff7f;
            }
            100% {
                box-shadow: 0 0 5px #00ff7f;
            }
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-10px);
            }
            75% {
                transform: translateX(10px);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes gradientBackground {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        body {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
            background: url("backs.jpg") no-repeat center center fixed;
            background-size: cover;
        }

        .login-container {
            background: rgba(0, 0, 0, 0.9);
            padding: 60px 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
            max-width: 380px;
            width: 100%;
            height: 65vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            animation: slideInRight 1s cubic-bezier(0.23, 1, 0.32, 1), glow 3s infinite;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-right: 5vw;
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 30px rgba(1, 12, 6, 0.841);
        }

        .login-container h2 {
            color: white;
            margin-bottom: 15px;
            font-size: 30px;
            font-weight: 600;
            animation: fadeIn 1.5s ease-in;
        }

        input {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input:focus {
            border-color: #00ff7f;
            box-shadow: 0 0 15px rgba(0, 255, 127, 0.5);
            transform: scale(1.02);
        }

        button {
            width: 107%;
            padding: 15px;
            background: #623DB9;
            color: black;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(89, 0, 255, 0.5);
        }

        button::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            transition: all 0.5s;
        }

        button:hover::after {
            transform: translateX(100%) rotate(45deg);
        }

        button:disabled {
            background: gray;
            cursor: not-allowed;
            animation: pulse 1.5s infinite;
        }

        .error {
            color: #ff4444;
            font-size: 14px;
            margin-bottom: 10px;
            animation: shake 0.4s ease-in-out;
        }

        .captcha-container {
            margin-top: 10px;
            font-size: 14px;
            animation: fadeIn 0.6s ease-out;
            color: rgb(228, 233, 238);
        }

        .captcha-container input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            background: white;
            color: rgb(0, 0, 0);
            transition: transform 0.3s;
        }

        .captcha-container input:focus {
            transform: scale(1.02);
        }

        @media (max-width: 768px) {
            .login-container {
                width: 90%;
                padding: 50px;
                animation: slideInRight 0.8s cubic-bezier(0.23, 1, 0.32, 1);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <form action="login.php" method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <div class="captcha-container">
                <p>Enter the following CAPTCHA: <strong id="captcha-question"></strong></p>
                <input type="text" name="captcha" placeholder="Enter CAPTCHA" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>

    <script>
     
        function generateCaptcha() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let captcha = '';
            for (let i = 0; i < 6; i++) {
                captcha += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return captcha;
        }

        
        document.addEventListener('DOMContentLoaded', function() {
            const captcha = generateCaptcha();
            document.getElementById('captcha-question').textContent = captcha;
            
           
            const captchaInput = document.createElement('input');
            captchaInput.type = 'hidden';
            captchaInput.name = 'captcha_answer';
            captchaInput.value = captcha;
            document.querySelector('form').appendChild(captchaInput);
        });
    </script>
</body>
</html>