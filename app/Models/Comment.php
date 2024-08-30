<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Comment extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'content',
        'user_id',
        'post_id',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function post() {
        return $this->belongsTo(Post::class);
    }
    
}
