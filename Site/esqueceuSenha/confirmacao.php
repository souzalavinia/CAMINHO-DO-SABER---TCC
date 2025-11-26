<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Código</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center !important;
            min-height: 100vh;
            background: white;
            background-size: cover;
        }

        .loginDois {
            width: 100%;
            height: 100%;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center !important;
        }

        .login-container {
            background-color: #fff; 
            border-radius: 8px;
            box-shadow: 0 0 15px 0 rgba(0, 0, 0, 0.6); 
            border: 2px solid #0173ed; 
            padding: 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            margin: 0 auto;
        }

        .login-form {
            display: flex;
            flex-direction: column;
        }

        .heading {
            color: #000; /* Cor preta */
            font-weight: 500;
            font-size: 40px;
            margin-bottom: 5px;
            font-family: 'Times New Roman', Times, serif;
            margin-top: 25px;
            margin-bottom: 20px;
        }

        .paragraph {
            color: #000; /* Cor preta */
            font-weight: 400;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group input {
            background: none;
            border: 1px solid #353535;
            padding: 15px 23px;
            font-size: 16px;
            border-radius: 8px;
            color: #000; /* Cor preta para as letras */
            width: 95%;
        }

        .input-group input:focus {
            border-color: #0173ed;
            outline: none;
        }

        button {
            padding: 15px;
            border: none;
            border-radius: 8px;
            background-color: #0173ed;
            color: #ffffff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0173ed;
        }

        .bottom-text {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
            color: #000; /* Cor preta */
            font-size: 15px;
            font-weight: 400;
        }

        .bottom-text a {
            color: #0173ed;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .bottom-text a:hover {
            color: #3f95f2;
        }

        .show-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: black; /* Cor preta para o ícone do olho */
        }

        .verficação{
            color: #000; /* Cor preta */
            font-weight: 400;
            font-size: 15px;
            margin-bottom: 15px;
            text-align: left;
        }
    </style>
</head>
<body>

    <div class="loginDois">
        <div class="login-container">
            <form class="login-form" method="POST" action="confirmacao.php">
                <p class="heading">Insira o código de <br> Verificação </p>

                <p class="verficação">
                    Por favor, verifique seu e-mail para confirmar sua conta. Enviamos um link de verificação para o endereço de e-mail que você forneceu.
                    <br><br>
                    <b>Não recebeu o e-mail?</b>
                    Verifique sua caixa de spam ou clique no botão abaixo para reenviar o e-mail.
                </p>
                <br>

                <div class="input-group">
                    <input required type="number" name="codVerificacao" id="codVerificacao" placeholder="123456" min="0">
                </div>

                <button type="submit">Verificar</button>
                <div class="bottom-text">
                    <p> Não recebeu nenhum código? <a href="reenviarCodigo.php" class="up">Reenviar código</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
