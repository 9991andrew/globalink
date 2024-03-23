<?php


/*
 This file is basically just for unit-testing as I develop classes
*/
include 'includes/mw_header.php';


//require 'vendor/autoload.php';
//require 'config/database.php';
//use Illuminate\Database\Capsule\Manager as Capsule;
//$capsule = new Capsule;
//$capsule->addConnection([
//   'driver' => 'mysql', 'mega', 'mega', '3!Jr^@8w^gXcPuP6'
//]);
//
//$capsule->bootEloquent();


echo '<pre><code>';

echo '</code></pre>';
?>



<!-- Testing Tailwind drop shadows -->
<div class="p-10 h-screen text-gray-700 bg-white dark:bg-gray-700 text-center ">

    <div class="text-center">
        <button class="btn" onclick="toggleDarkMode()">Toggle Dark Mode</button>
    </div>

    <strong>Tailwind drop-shadows</strong>
    <div class="mb-10 flex justify-center items-center space-x-6">
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-sm">drop-shadow-sm</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow">drop-shadow</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-md">drop-shadow-md</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-lg">drop-shadow-lg</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-xl">drop-shadow-xl</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-2xl">drop-shadow-2xl</div>
    </div>

    <strong class="text-xl">Tailwind shadows</strong>
    <div class="mb-10 flex justify-center items-center space-x-6">
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-sm">shadow-sm</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow">shadow</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-md">shadow-md</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-lg">shadow-lg</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-xl">shadow-xl</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-2xl">shadow-2xl</div>
    </div>

    <strong>JD's Dark Drop shadow variants</strong>
    <div class="mb-10 flex justify-center items-center space-x-6">
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-sm-dark">drop-shadow-sm-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-dark">drop-shadow-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-md-dark">drop-shadow-md-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-lg-dark">drop-shadow-lg-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-xl-dark">drop-shadow-xl-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg drop-shadow-2xl-dark">drop-shadow-2xl-dark</div>
    </div>

    <strong>JD's Dark shadow variants</strong>
    <div class="mb-10 flex justify-center items-center space-x-6">
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-sm-dark">shadow-sm-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-dark">shadow-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-md-dark">shadow-md-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-lg-dark">shadow-lg-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-xl-dark">shadow-xl-dark</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-2xl-dark">shadow-2xl-dark</div>
    </div>

    <strong>Inset Shadows</strong>
    <div class="mb-10 flex justify-center items-center space-x-6">
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-inner">shadow-inner</div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 dark:text-white w-40 h-40 rounded-lg shadow-inner-dark">shadow-inner-dark</div>
    </div>

</div>

<script>

</script>
<?php

include 'includes/mw_footer.php';
