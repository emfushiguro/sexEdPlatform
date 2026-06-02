<?php

use App\Http\Controllers\Connector\DashboardController;
use App\Http\Controllers\Connector\HomeController;
use App\Http\Controllers\Connector\InvitationController;
use App\Http\Controllers\Connector\MemberController;
use App\Http\Controllers\Connector\RegistrationController;
use App\Http\Controllers\Connector\RoleController;
use App\Http\Controllers\Connector\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/connectors', [HomeController::class, 'index'])->name('connectors.index');
    Route::get('/connectors/register', [RegistrationController::class, 'create'])->name('connectors.register');
    Route::post('/connectors/register', [RegistrationController::class, 'store'])->name('connectors.store');
    Route::get('/connector/{connector}/status', [RegistrationController::class, 'status'])->name('connector.status');
    Route::post('/connector/{connector}/withdraw', [RegistrationController::class, 'withdraw'])->name('connector.withdraw');

    Route::get('/connector/{connector}/dashboard', [DashboardController::class, 'index'])->name('connector.dashboard');
    Route::get('/connector/{connector}/seminars', [DashboardController::class, 'seminars'])->name('connector.seminars');
    Route::get('/connector/{connector}/modules', [DashboardController::class, 'modules'])->name('connector.modules');
    Route::get('/connector/{connector}/educators', [DashboardController::class, 'educators'])->name('connector.educators');

    Route::get('/connector/{connector}/members', [MemberController::class, 'index'])->name('connector.members.index');
    Route::patch('/connector/{connector}/members/{membership}/role', [MemberController::class, 'updateRole'])->name('connector.members.role');
    Route::delete('/connector/{connector}/members/{membership}', [MemberController::class, 'destroy'])->name('connector.members.destroy');

    Route::post('/connector/{connector}/invitations', [InvitationController::class, 'store'])->name('connector.invitations.store');
    Route::post('/connector/{connector}/invitations/{invitation}/accept', [InvitationController::class, 'accept'])->name('connector.invitations.accept');
    Route::post('/connector/{connector}/invitations/{invitation}/reject', [InvitationController::class, 'reject'])->name('connector.invitations.reject');
    Route::post('/connector/{connector}/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('connector.invitations.resend');

    Route::get('/connector/{connector}/roles', [RoleController::class, 'index'])->name('connector.roles.index');
    Route::post('/connector/{connector}/roles', [RoleController::class, 'store'])->name('connector.roles.store');
    Route::put('/connector/{connector}/roles/{role}', [RoleController::class, 'update'])->name('connector.roles.update');
    Route::delete('/connector/{connector}/roles/{role}', [RoleController::class, 'destroy'])->name('connector.roles.destroy');

    Route::get('/connector/{connector}/subscription', [SubscriptionController::class, 'show'])->name('connector.subscription');
});
