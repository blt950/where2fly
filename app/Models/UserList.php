<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserList extends Model
{
    use HasFactory;

    public static function getMapListArray(User $user){
        $data = UserList::where('user_id', $user->id)->with('airports')->get()->map(function ($list) {
            return [
                'id' => $list->id,
                'name' => $list->name,
                'color' => $list->color,
                'airports' => $list->airports->map(function ($airport) {
                    return [
                        'icao' => $airport->icao,
                        'lat' => $airport->coordinates->latitude,
                        'lon' => $airport->coordinates->longitude,
                    ];
                }),
            ];
        });

        return $data;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function simulator()
    {
        return $this->belongsTo(Simulator::class);
    }

    public function airports()
    {
        return $this->belongsToMany(Airport::class);
    }
}
