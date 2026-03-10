<?php

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\LiveshowLinkController;
use App\Http\Controllers\RecommendationLinkController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

//endpoint
Route::get('/x', function () {
    return 'API';
});


Route::post('users/login', [UserController::class, 'login']);
Route::post('users/logout', [UserController::class, 'logout']);
Route::post('users', [UserController::class, 'store']);


//region usersme
Route::get('usersme', [UserController::class, 'indexSelf'])
    ->middleware(['auth:sanctum', 'ability:usersme:get']);

Route::patch('usersme', [UserController::class, 'updateSelf'])
    ->middleware(['auth:sanctum', 'ability:usersme:patch']);

Route::patch('usersmeupdatepassword', [UserController::class, 'updatePassword'])
->middleware('auth:sanctum', 'ability:usersme:updatePassword');    

Route::delete('usersme', [UserController::class, 'destroySelf'])
    ->middleware(['auth:sanctum', 'ability:usersme:delete']);
//endregion

//region Liveshows and Mixes
Route::get('liveshow-links', [LiveshowLinkController::class, 'index']);
Route::post('liveshow-links', [LiveshowLinkController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::delete('liveshow-links/{id}', [LiveshowLinkController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'ability:admin']);
//endregion

//region Recommendations
Route::get('recommendation-links', [RecommendationLinkController::class, 'index']);
Route::post('recommendation-links', [RecommendationLinkController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::delete('recommendation-links/{id}', [RecommendationLinkController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'ability:admin']);
//endregion

//region admin endpoint
Route::get('users', [UserController::class, 'index'])
    ->middleware(['auth:sanctum', 'ability:admin']);

Route::get('users/{id}', [UserController::class, 'show'])
    ->middleware(['auth:sanctum', 'ability:admin']);

Route::patch('users/{id}', [UserController::class, 'update'])
    ->middleware(['auth:sanctum', 'ability:admin']);

Route::delete('users/{id}', [UserController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'ability:admin']);
//endregion

//region Tracks
Route::get('tracks', [TrackController::class, 'index']);
Route::get('tracks/{id}/preview', [TrackController::class, 'preview']);
Route::get('tracks/{id}/source', [TrackController::class, 'source']);
Route::get('tracks/{id}', [TrackController::class, 'show']);
Route::post('tracks/analyze-upload', [TrackController::class, 'analyzeUpload'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::post('tracks', [TrackController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::post('tracks/{id}/regenerate-preview', [TrackController::class, 'regeneratePreview'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::patch('tracks/{id}', [TrackController::class, 'update'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::delete('tracks/{id}', [TrackController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'ability:admin']);
//endregion

//region Genres
Route::get('genres', [GenreController::class, 'index']);
Route::get('genres/{id}', [GenreController::class, 'show']);
Route::post('genres', [GenreController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::patch('genres/{id}', [GenreController::class, 'update'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::delete('genres/{id}', [GenreController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'ability:admin']);
//endregion

//region Artists
Route::get('artists', [ArtistController::class, 'index']);
Route::get('artists/{id}', [ArtistController::class, 'show']);
Route::post('artists', [ArtistController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::patch('artists/{id}', [ArtistController::class, 'update'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::delete('artists/{id}', [ArtistController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'ability:admin']);
//endregion

//region Albums
Route::get('albums', [AlbumController::class, 'index']);
Route::get('albums/{id}', [AlbumController::class, 'show']);
Route::post('albums', [AlbumController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::patch('albums/{id}', [AlbumController::class, 'update'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::delete('albums/{id}', [AlbumController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::patch('albums/{id}/tracks', [AlbumController::class, 'syncTracks'])
    ->middleware(['auth:sanctum', 'ability:admin']);
//endregion

//region Carts
Route::get('carts', [CartController::class, 'index'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::get('carts/{id}', [CartController::class, 'show'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::post('carts', [CartController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::delete('carts/{id}', [CartController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'ability:admin']);

Route::get('my-carts', [CartController::class, 'indexSelf'])
    ->middleware(['auth:sanctum', 'ability:carts:self:get']);
Route::post('my-carts', [CartController::class, 'storeSelf'])
    ->middleware(['auth:sanctum', 'ability:carts:self:post']);
Route::delete('my-carts/{id}', [CartController::class, 'destroySelf'])
    ->middleware(['auth:sanctum', 'ability:carts:self:delete']);
//endregion

//region Cart Items
Route::get('cart-items', [CartItemController::class, 'index'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::get('cart-items/{id}', [CartItemController::class, 'show'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::post('cart-items', [CartItemController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::patch('cart-items/{id}', [CartItemController::class, 'update'])
    ->middleware(['auth:sanctum', 'ability:admin']);
Route::delete('cart-items/{id}', [CartItemController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'ability:admin']);

Route::get('my-cart-items', [CartItemController::class, 'indexSelf'])
    ->middleware(['auth:sanctum', 'ability:cart-items:self:get']);
Route::post('my-cart-items', [CartItemController::class, 'storeSelf'])
    ->middleware(['auth:sanctum', 'ability:cart-items:self:post']);
Route::patch('my-cart-items/{id}', [CartItemController::class, 'updateSelf'])
    ->middleware(['auth:sanctum', 'ability:cart-items:self:patch']);
Route::delete('my-cart-items/{id}', [CartItemController::class, 'destroySelf'])
    ->middleware(['auth:sanctum', 'ability:cart-items:self:delete']);
//endregion
