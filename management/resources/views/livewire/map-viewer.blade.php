<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>3D Map with Babylon.js</title>
  <script src="https://cdn.babylonjs.com/babylon.js"></script>
  <script src="https://cdn.babylonjs.com/loaders/babylonjs.loaders.min.js"></script>
  <style>
    #renderCanvas {
        width: 100%;
        height: 100%;
        display: block;
    }
  </style>
</head>
<body>
  <canvas id="renderCanvas"></canvas>
  <script>
    // Call the loadModels function with your 2D array of links
    var tileLinks = <?php echo json_encode($map->modelUrls); ?>;


    function flattenAndUnique(arr) {
      const flattenedArray = arr.flat(); // Flattening the 2D array
      const uniqueValues = [...new Set(flattenedArray)]; // Removing duplicates using Set
      return uniqueValues;
    }

    const uniqueTileLinks = flattenAndUnique(tileLinks);




    // Create the Babylon.js engine
    const canvas = document.getElementById('renderCanvas');
    const engine = new BABYLON.Engine(canvas, true);
    engine.enableOfflineSupport = false;
    engine.doNotHandleContextLost = true;

    // Create the scene
    var createScene = function () {
      var scene = new BABYLON.Scene(engine);

      // Create a camera
      var camera = new BABYLON.ArcRotateCamera("camera", 0, 0, 0, new BABYLON.Vector3(10, 0, 10), scene);
      camera.setPosition(new BABYLON.Vector3(5, 30, -5));
      camera.attachControl(canvas, true);

      // Create a light source
      var light = new BABYLON.HemisphericLight("light", new BABYLON.Vector3(0, 1, 0), scene);

      return scene;
    };

    var scene = createScene();
    scene.blockMaterialDirtyMechanism = true;

    // Define the dimensions of the square map
    let mapXSize =tileLinks.length// Assuming a 5x5 map
    let mapYSize =tileLinks[0].length// Assuming a 5x5 map
    const tileSize = 2; // Adjust the tile size as needed


    // let mappedLinks = {};
    // (async () => {


    //   for (let i = 0; i < uniqueTileLinks.length; i++) {
    //     let link = uniqueTileLinks[i];
    //     let assetArrayBuffer = await BABYLON.Tools.LoadFileAsync(link, true);
    //     let assetBlob = new Blob([assetArrayBuffer]);
    //     let assetUrl = URL.createObjectURL(assetBlob);

    //     if(mappedLinks[link]==undefined){
    //       mappedLinks[link] = assetUrl;
    //     }

    //     // await BABYLON.SceneLoader.AppendAsync(assetUrl, undefined, scene, undefined, ".glb");
    //   }

    // })();

    // async function preloadModels(uniqueTileLinks) {
    //   let mappedModels = {};

    //   for (link in uniqueTileLinks) {
    //     mappedModels[link] = await BABYLON.SceneLoader.ImportMeshAsync('', link, '', scene);
    //   }

    //   return mappedModels;
    // }

    // preloadModels(uniqueTileLinks).then((mappedModels) => {
    //   // Models have been preloaded, including materials
    //   // You can store the models and use them later when needed
    //   console.log('Models preloaded:', Object.keys(mappedModels));
    // });


    // // Load the tile models and instantiate them in a grid fashion
    // (async () => {
    //   for (let i = 0; i < mapXSize; i++) {
    //     for (let j = 0; j < mapYSize; j++) {
    //       const link = tileLinks[i][j];

    //       BABYLON.SceneLoader.ImportMesh('', '', mappedLinks[link], scene, (meshes) => {
    //       // Get the loaded tile mesh and its materials
    //       const tileMesh = meshes[0];
    //       const tileMaterials = tileMesh.materials;

    //       // Adjust the position of the tile mesh
    //       tileMesh.position.x = i * tileSize;
    //       tileMesh.position.z = j * tileSize;

    //       // Clone the materials to preserve texture assignments
    //       const clonedMaterials = tileMaterials.map((material) => material.clone());

    //       // Assign the cloned materials to the cloned tile mesh
    //       tileMesh.materials = clonedMaterials;
    //       });
    //     }
    //   }
    // })();


    // // Start the engine render loop
    // engine.runRenderLoop(() => {
    //     scene.render();
    // });

    // Preload the unique tile models
    let uniqueTileModels = new Map();
    let loadedTileModels = [];

    const preloadTileModel = (link) => {
      return new Promise((resolve, reject) => {
        BABYLON.SceneLoader.ImportMeshAsync('', '', link, scene)
          .then((result) => {
            // Get the loaded tile mesh and its materials
            const tileMesh = result.meshes[0];
            // const tileMaterials = tileMesh.materials;

            // Clone the materials to preserve texture assignments
            // const clonedMaterials = tileMaterials.map((material) => material.clone());

            // Assign the cloned materials to the cloned tile mesh
            // tileMesh.materials = clonedMaterials;

            resolve(tileMesh);
          })
          .catch((error) => reject(error));
      });
    };

    // Instantiate the tiles in the scene using the preloaded models
    const instantiateTiles = () => {
      for (let i = 0; i < mapXSize; i++) {
        for (let j = 0; j < mapYSize; j++) {
          const link = tileLinks[i][j];
          let tileModel = uniqueTileModels.get(link);

          if (!tileModel) {
            tileModel = preloadTileModel(link);
            uniqueTileModels.set(link, tileModel);
          }

          tileModel
            .then((resolvedTileModel) => {
              // Check if the tile model is unique
              let isUniqueModel = tileLinks
                .map((row) => row.filter((item) => item === link).length)
                .reduce((acc, count) => acc + count, 0) === 1;

              let clonedTileModel = isUniqueModel
                ? resolvedTileModel // Use the loaded model directly
                : resolvedTileModel.clone(); // Clone the model

              // Adjust the position of the tile model
              clonedTileModel.position.x = i * tileSize;
              clonedTileModel.position.z = j * tileSize;

              // Add the cloned tile model to the scene
              scene.addMesh(clonedTileModel);

              // Store the cloned tile model for future use
              loadedTileModels.push(clonedTileModel);

              // Check if all tiles have been loaded
              if (loadedTileModels.length === mapXSize * mapYSize) {
                // Start the engine render loop
                scene.clearCachedVertexData();
                scene.cleanCachedTextureBuffer();
                scene.blockMaterialDirtyMechanism = false;
                engine.runRenderLoop(() => {
                  scene.render();
                });
              }
            })            
            .catch((error) => {
              console.error(`Failed to load tile model for link ${link}:`, error);
            });
        }
      }
    };

    // Start preloading the tile models
    instantiateTiles();

    window.addEventListener("resize", function () {
        engine.resize();
    });
  </script>
</body>
</html>
