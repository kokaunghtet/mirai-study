<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $tags = Tag::factory(6)->create();
        $users = User::all();

        $users->each(function (User $user) use ($tags, $users) {
            $posts = Post::factory(3)->create(['user_id' => $user->id]);

            $posts->each(function (Post $post) use ($tags, $users) {

                $post->tags()->attach(
                    $tags->random(rand(1, 3))->pluck('id')
                );

                $comments = Comment::factory(3)->create([
                    'post_id' => $post->id,
                    'user_id' => $users->random()->id,
                ]);

                $comments->each(function (Comment $comment) use ($post, $users) {
                    Comment::factory(rand(1, 2))->create([
                        'post_id'   => $post->id,
                        'user_id'   => $users->random()->id,
                        'parent_id' => $comment->id,
                    ]);
                });

                // Collect used user IDs to avoid duplicate likes on same post
                $usersWhoLiked = collect();
                $users->random(rand(2, 8))->each(function (User $liker) use ($post, &$usersWhoLiked) {
                    if ($usersWhoLiked->contains($liker->id)) return;
                    $usersWhoLiked->push($liker->id);

                    \App\Models\PostLike::create([
                        'user_id' => $liker->id,
                        'post_id' => $post->id,
                    ]);
                });
            });
        });

        $users->each(function (User $follower) use ($users) {
            $users->where('id', '!=', $follower->id)
                ->random(rand(2, 5))
                ->each(function (User $following) use ($follower) {
                    \App\Models\Follow::firstOrCreate([
                        'follower_id'  => $follower->id,
                        'following_id' => $following->id,
                    ], ['status' => 'accepted']);
                });
        });
    }
}
