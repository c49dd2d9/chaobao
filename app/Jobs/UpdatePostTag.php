<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdatePostTag implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data; 
        $tagidList = DB::table('tag_data')->where('post_id', $data['id'])->pluck('tag_id');
        $tagList = DB::table('tags')->whereIn('id', $tagidList)->get();
        $tagData = [];
        foreach ($tagList as $item) {
            $itemData = [
                'id' => $item->id,
                'name' => $item->name,
            ];
            array_push($tagData, $itemData);
        }
        
        Post::where('id', $data['id'])->update([
            'tag_json' => json_encode($tagData),
        ]);
    }
}
