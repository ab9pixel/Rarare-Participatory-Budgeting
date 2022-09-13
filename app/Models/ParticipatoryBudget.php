<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipatoryBudget extends Model
{
    /**
     * Get all of the comments for the ParticipatoryBudget
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    protected $appends = ['liked_users','user','exist_users','marked_option','progress','total_record'];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'parent_id', 'id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'parent_id', 'id');
    }

    public function options()
    {
        return $this->hasMany(Option::class, 'parent_id', 'id');
    }

    public function user_option()
    {
        return $this->hasMany(UserOption::class, 'parent_id', 'id');
    }

    public function getLikedUsersAttribute()
    {
        return $this->likes->pluck('user_id');
    }

    public function getExistUsersAttribute()
    {
        return $this->user_option->pluck('user_id');
    }

    public function getProgressAttribute()
    {
        $user_option=count($this->user_option()->get());
        $audience=$this->audience;
        $percentage=round(($user_option/$audience)*100);
        return $percentage;
    }

    public function getTotalRecordAttribute()
	{
		return $this->count();
	}

    public function getUserAttribute()
    {
        $user_id=$this->user_id;
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://rrci.staging.rarare.com/user/'.$user_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function getMarkedOptionAttribute()
    {
        $option_array=array();
        $option=$this->options;
        foreach($option as $item){
            $option_array[$item->id]=count($item->user_option);
        }

        return $option_array;
    }

    public function getStatusAttribute(){
	    $start = gmdate('Y-m-d H:i:s', strtotime("$this->start_date $this->start_time"));
	    $end = gmdate('Y-m-d H:i:s', strtotime("$this->end_date $this->end_time"));
	    $now=date("Y-m-d H:i:s");

	    if($start > $now && $end < $now){
	    	return 1;
	    }

	    if($start < $now){
	    	return 0;
	    }

	    return 2;
    }
}
