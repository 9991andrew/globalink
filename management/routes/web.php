<?php

use App\Models\Player;
use App\Http\Controllers\MapTileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * These views require an authenticated user
 */
Route::middleware('auth')->group(function() {



    // Default route is to show the dashboard
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');


    
    Route::get('/users',
\App\Http\Livewire\ShowUsers::class
    )->name('users');

    Route::get('/players',
        \App\Http\Livewire\ShowPlayers::class
    )->name('players');

    Route::get('/items',
        \App\Http\Livewire\ShowItems::class
    )->name('items');

    Route::get('/item-categories',
        \App\Http\Livewire\ShowItemCategories::class
    )->name('item-categories');

    Route::get('/birthplaces',
        \App\Http\Livewire\ShowBirthplaces::class
    )->name('birthplaces');

    Route::get('/avatar-images',
        \App\Http\Livewire\ShowAvatarImages::class
    )->name('avatar-images');

    Route::get('/avatar-image-types',
        \App\Http\Livewire\ShowAvatarImageTypes::class
    )->name('avatar-image-types');

    Route::get('/map-types',
        \App\Http\Livewire\ShowMapTypes::class
    )->name('map-types');

    Route::get('/maps',
        \App\Http\Livewire\ShowMaps::class
    )->name('maps');

    Route::get('/maps/{map}',
        \App\Http\Livewire\MapTileEditor::class
    )->name('map');

    Route::get('/maps/model/{map}',
        \App\Http\Livewire\MapViewer::class
    )->name('mapViewer');

    Route::post('/maps/{map}/tile', [MapTileController::class, 'update']
    )->whereNumber('map')->name('mapTileUpdate');

    Route::get('/maps/{map}/tile', [MapTileController::class, 'info']
    )->whereNumber('map')->name('mapTileInfo');

    Route::get('/map-tile-types',
        \App\Http\Livewire\ShowMapTileTypes::class
    )->name('map-tile-types');

    Route::get('/map-tile-types/{m_id}',
          \App\Http\Livewire\ModelViewer::class
    )->name('modelviewer');

    Route::get('/npcs',
        \App\Http\Livewire\ShowNpcs::class
    )->name('npcs');

    Route::get('/professions',
        \App\Http\Livewire\ShowProfessions::class
    )->name('professions');

    Route::get('/management-users',
        \App\Http\Livewire\ShowManagementUsers::class
    )->name('management-users');

    Route::get('/item-icons',
        \App\Http\Livewire\ShowItemIcons::class
    )->name('item-icons');

    Route::get('/npc-icons',
        \App\Http\Livewire\ShowNpcIcons::class
    )->name('npc-icons');

    Route::get('/quests',
        \App\Http\Livewire\ShowQuests::class
    )->name('quests');

    Route::get('/quest-types',
        \App\Http\Livewire\ShowQuestTypes::class
    )->name('quest-types');

    Route::get('/quest-tools',
        \App\Http\Livewire\ShowQuestTools::class
    )->name('quest-tools');

    Route::get('/buildings',
        \App\Http\Livewire\ShowBuildings::class
    )->name('buildings');

    Route::get('/skills',
        \App\Http\Livewire\ShowSkills::class
    )->name('skills');

    Route::get('/languages',
        \App\Http\Livewire\ShowLanguages::class
    )->name('languages');

    Route::get('/wsnlp',
        \App\Http\Livewire\ShowWsnlp::class
    )->name('wsnlp');

    Route::get('/maps/wsnlp/{map}',
        \App\Http\Livewire\ShowMapWsnlp::class
    )->name('mapwsnlp');

    Route::get('/maps/ask4/{map}',
        \App\Http\Livewire\ShowMapAsk4::class
    )->name('mapask4');
    
    Route::get('/monster',
    \App\Http\Livewire\ShowMonster::class
    )->name('monster');
    
    Route::get('/potions',
    \App\Http\Livewire\ShowPotions::class
    )->name('potions');

    Route::get('/weapon',
    \App\Http\Livewire\ShowWeapon::class
    )->name('weapon');
    Route::get('/armors',
    \App\Http\Livewire\ShowArmors::class
    )->name('armors');
    Route::view('/help', 'guide')->name('help');

});



require __DIR__.'/auth.php';
