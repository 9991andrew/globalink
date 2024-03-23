<!DOCTYPE html>
<html>
<head>
    <title>3D Model Viewer</title>
    <!-- Include Babylon.js and BabylonViewer libraries -->
    <script src="https://cdn.babylonjs.com/babylon.js"></script>
    <script src="https://cdn.babylonjs.com/viewer/babylon.viewer.js"></script>

</head>
<body>
    <babylon 
    templates.nav-bar.html=
    '<style>
    nav-bar {
        position: absolute;
        height: 48px;
        width: 100%;
        top: 10px;
        display: flex;
        justify-content: center;
    }

    nav-bar .nav-container {
        display: flex;
        flex-direction: row;
        margin: 0 10px;
        height: 100%;
        width: 100%;
        justify-content: center;
    }

    nav-bar .animation-control {
        background-color: rgba(91, 93, 107, .75);
        display: flex;
        flex-direction: row;
        height: 100%;
        width: 100%;
        max-width: 1280px;
        justify-content: center;
    }

    nav-bar .flex-container {
        display: flex;
        flex-direction: row;
        justify-content: center;
        height: 100%;
        width: 100%;
    }

    nav-bar button {
        background: none;
        border: none;
        color: white;
        margin: 0;
        padding: 0;

        height: 100%;
        min-width: 48px;
        cursor: pointer;
    }

    nav-bar button:hover,
    nav-bar button:active,
    nav-bar button:focus {
        background: none;
        border: none;
        outline: none;
    }

    nav-bar button:hover {
        background-color: rgba(22, 24, 26, .20);
    }

    .logo-button {
        display: flex;
        align-items: center;
        flex-direction: row;
        justify-content: center;
        background-color: rgba(24,24,24,0);
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
    }

    .logo-button a {
        display: block;
        background-color: rgba(24,24,24,0.8);
        color: white;
        padding: 10px 40px;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
    }

    .logo-button,
    .animation-label,
    .types-icon,
    .help,
    .speed {
        display: none;
    }


    @media screen and (min-width: 540px) {
        .logo-button {
            display: flex;

        }
    }

    @media screen and (min-width: 1024px) {

        nav-bar button.animation-buttons {
            padding: 0 8px;
            justify-content: left;
        }

        nav-bar button.animation-buttons>div {
            display: flex;
            pointer-events: none;
        }

    }
    
    nav-bar .nav-container {
        justify-content: space-between;
    }

    .back-button {
        position: fixed;
        bottom: 10px;
        left: 10px;
        background-color: #4CAF50;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
    }
</style>

<div class="nav-container navbar-control">
    <div class="logo-button">
        <a href="#" onclick="history.back()">
            Back
        </a>
    </div>
</div>'
    model='https://megaworld.game-server.ca/models/tile/tile{{sprintf('%04d', $m_id)}}.gltf?u={{time()}}'>
    </babylon>
</body>
</html>