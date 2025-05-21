<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome Page</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
      color: white;
      overflow-x: hidden;
    }

    .top-bar {
      display: flex;
      justify-content: flex-end;
      padding: 20px 40px;
    }

    .top-bar a {
      background-color: #00d9ff;
      color: #000;
      padding: 10px 22px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: bold;
      transition: 0.3s;
      box-shadow: 0 0 15px rgba(0, 217, 255, 0.5);
    }

    .top-bar a:hover {
      background-color: #00bcd4;
      box-shadow: 0 0 25px rgba(0, 217, 255, 0.9);
    }

    .main-container {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 50px 40px;
      min-height: 85vh;
    }

    .text-section {
      flex: 1;
      padding: 30px;
      animation: slideInRight 1s ease;
    }

    .text-section h1 {
      font-size: 52px;
      margin-bottom: 20px;
      color: #00f7ff;
    }

    .text-section p {
      font-size: 20px;
      line-height: 1.7;
      color: #e0f7fa;
    }

    .image-section {
      flex: 1;
      padding: 30px;
      animation: slideInLeft 1s ease;
    }

    .image-section img {
      width: 80%;
      max-width: 550px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.4);
      transition: transform 0.4s ease;
    }

    .image-section img:hover {
      transform: scale(1.05);
    }

    @keyframes slideInRight {
      from { transform: translateX(100px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    @keyframes slideInLeft {
      from { transform: translateX(-100px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    @media(max-width: 768px) {
      .main-container {
        flex-direction: column-reverse;
        text-align: center;
        padding: 20px;
      }

      .text-section h1 {
        font-size: 36px;
      }

      .text-section p {
        font-size: 18px;
      }
    }
  </style>
</head>
<body>

  <div class="top-bar">
    <a href="login">🔐 Login</a>
  </div>

  <div class="main-container">
    <div class="text-section">
      <h1>Wasaarada Maaliyada EE SSC Khaatumo</h1>
      <p>
        System-kan waxaa si gaar ah loogu talagalay maamulka, dabagalka, iyo ilaalinta dhaqaalaha SSC Khaatumo. Waxuu fududeeyaa diiwaangelinta, bixinta, iyo la socodka kharashaadka iyo dakhliga. <br>

      </p>
    </div>
    <div class="image-section">
      <img src="img/logo2.png" alt="Smart System">
    </div>
  </div>

</body>
</html>
