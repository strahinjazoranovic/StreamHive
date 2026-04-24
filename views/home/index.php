<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../public/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
      rel="stylesheet"
    />
    <title>StreamHive</title>
  </head>
  <body>
    <div>
      <div class="navbar">
        <div class="navbar-left">
          <button class="icon-button">
            <img
              src="../../public/logos/hamburgermenu.svg"
              class="img-hover"
              alt="sidebar button"
              draggable="false"
            />
          </button>

          <h1 class="text-header">
            Stream<span class="text-accent">Hive</span>
          </h1>
        </div>

        <div class="navbar-center">
          <div class="search-bar">
            <input type="text" class="search-input" placeholder="Zoek" />
            <button class="icon-button">
              <img
                src="../../public/logos/search.svg"
                class="img-hover"
                alt="Search"
                draggable="false"
              />
            </button>
          </div>
        </div>

        <div class="navbar-right">
          <button class="icon-button">
            <img
              src="../../public/logos/upload.svg"
              class="img-hover"
              alt="Upload button"
              draggable="false"
            />
          </button>

          <button class="icon-button">
            <img
              src="../../public/logos/profile.svg"
              class="img-hover"
              alt="Profile button"
              draggable="false"
            />
          </button>
        </div>
      </div>
      <div class="container">
        <div class="sidebar"></div>
        <div class="content"></div>
      </div>
    </div>
  </body>
</html>
