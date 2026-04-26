<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use CanResetPassword, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAirportsFromLists()
    {
        return $this->lists()->with('airports', 'airports.metar', 'airports.runways')
            ->get()
            ->pluck('airports')
            ->flatten()
            ->all();
    }

    public function lists()
    {
        return $this->hasMany(UserList::class);
    }

    public function feedbackVotes()
    {
        return $this->hasMany(FeedbackVote::class, 'user_id');
    }
}
