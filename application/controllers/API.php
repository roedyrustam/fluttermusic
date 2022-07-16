<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(0);

class API extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->ci =& get_instance(); 
 
          
        date_default_timezone_set("Asia/Kolkata");
    }
    
    public function get_sender_id(){
        echo json_encode($this->db->select('settings_value as sender_id')->where(array('settings_name'=>'Sender Id'))->get('notification_settings')->result());
    }
    
    public function settingsFlag(){
        
        echo json_encode($db_result = $this->db->get('settings_flag')->result());
    }
    
    
    public function signup()
    {   
        $result=array();
        $data=array(
            'user_name' => $this->input->post('user_name'),
            'user_email' => $this->input->post('user_email'),
            'user_password' => $this->input->post('user_password'),
            'user_profile_pic' => $this->input->post('user_profile_pic'),
            'user_token' => $this->input->post('user_token'));
       
         $db_result = $this->db->get_where('user', array('user_email'=>$data['user_email']))->result();
       
        if(empty($db_result))
        {   
            $data['user_package_expiry_date']= date("Y-m-d H:i:s");
            $data['created_date']= date("Y-m-d H:i:s");
            $data['user_package_id']= '1';
            $this->db->insert('user',$data);
             
             
             $user_id=$this->db->insert_id();
        }
        else
        {
           
            $this->db->update('user',$data,array('user_email'=>$data['user_email']));
            $user_id = $db_result[0]->user_id ;

        }
        if($data['user_profile_pic'] != ''){
            
            $image = base64_decode($data['user_profile_pic']);
            $image_name = "user_".$user_id."_".date('Y-m-d-H-i-s').".jpg";
            $path = "uploads/user/";
            file_put_contents($path . $image_name, $image);
            
             $this->db->update('user',array('user_profile_pic' =>$image_name ),array('user_email'=>$data['user_email']));
            
            
        }
         $result = $this->db->get_where('user', array('user_email'=>$data['user_email']))->result();
         if(!empty($result)){
             $result[0]->user_profile_pic = 'uploads/user/'.$result[0]->user_profile_pic;
         }
        
        echo json_encode($result);
        
    }

    public function signin()
    {
        $data=array(
            'user_email' => $this->input->post('user_email'),
            'user_password' => $this->input->post('user_password'),
            );
            $user_token = $this->input->post('user_token');
       $result=array();
       $result = $this->db->get_where('user', $data)->result();
       if(!empty($result) && $result[0]->user_token != $user_token){
           $this->db->update('user',array('user_token'=>$user_token),$data);
           $result[0]->user_token = $user_token;
       }
       if(!empty($result)){
             $result[0]->user_profile_pic = 'uploads/user/'.$result[0]->user_profile_pic;
         }
       
        
        echo json_encode($result);
        
    }
    
    public function home_components(){
        $result = $this->db->select('home_components_id,home_components_name,home_components_order')->where(array('home_components_status'=>'ENABLE'))->order_by('home_components_order','ASC')->get('home_components')->result();
        echo json_encode($result);

    }
     
    public function home(){
        $user_id = $this->input->post('user_id');
        $start =($this->input->post('start') ? $this->input->post('start') : '');
        $end =($this->input->post('end') ? $this->input->post('end') : '');
            
            
        $is_myProfile = $this->input->post('is_myProfile');
        $home_components=strtolower($this->input->post("home_components_name"));
        $limit =$this->db->get_where('home_components',array('home_components_name' =>$home_components))->row();
        $result =array();
        
        if(!empty($limit)){
        $limit=$limit->home_components_item_display_count;
        $home_component =str_replace(' ','_',$home_components);
        
        switch ($home_component)
    {
        
      case 'banner_slider':
          
          $result = $this->db->select('*,CONCAT("uploads/banner/", banner_slider_image) as banner_slider_image')->where(array('banner_slider_status'=>'ENABLE'))->order_by('banner_slider_order','ASC')->order_by('created_date','desc')->get('banner_slider',$limit)->result();
          break;
          
          
      case 'most_played':
      case 'recently_played' : 
          
                  $this->db->select('music.music_id,music.artist_id,music.album_id,music.movie_id,music.category_id,music.music_title,concat("uploads/music/",music_file) as music_file,concat("uploads/music/image/",music_image) as music_image,music_duration, COUNT(recently_view_type_id) as playCount');
                  $this->db->join('music','music.music_id =recently_view.recently_view_type_id');
             if($is_myProfile)   
                     $this->db->where('recently_view.user_id',$user_id);
                      
                  $this->db->where(array('recently_view_type'=>'Music','music.music_status'=>'ENABLE'));
                  $this->db->group_by('recently_view_type_id');
            if($home_component == 'most_played')
                  $this->db->order_by('playCount', 'desc');
                  $this->db->order_by('recently_view.created_date','desc');
             if($start != '' and $end != '')
             {
                 $range = $end-$start; 
           $query=$this->db->get('recently_view', $range,$start);
             }
           else
           $query=$this->db->get('recently_view', $limit);
          $result = $query->result();
           foreach($result as $music){
               $music->album_name = ( isset($music->album_id) && ($music->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$music->album_id))->get('album')->row()->album_name) : '' ;
               $music->category_name = ( isset($music->category_id) && ($music->category_id != 0)) ?  ($this->db->select('category_name')->where(array('category_id'=>$music->category_id))->get('category')->row()->category_name) : '' ;
               $music->movie_name =( isset($music->movie_id) && ($music->movie_id != 0))  ?  ($this->db->select('movie_name')->where(array('movie_id'=>$music->movie_id))->get('movie')->row()->movie_name) : '' ;
                $music->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                $music->is_in_playlist = $this->db->get_where('user_playlist_music',array('user_id' => $user_id,'music_id'=>$music->music_id))->num_rows() ;
                $music->like_count = $this->db->get_where('liked',array('like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                
                
                $music->artists=array();
                    $artist_ids = array_filter(explode(',', $music->artist_id));
                    if(!empty($artist_ids)){
                        foreach($artist_ids as $artist_id) {
                            array_push($music->artists,array('artist_id'=>$artist_id,'artist_name'=>$this->db->get_where('artist',array('artist_id'=>$artist_id))->row()->artist_name));
                        }
                    }
          
           }
           
          break;
          
          
      case 'popular_albums':
          
          $this->db->select('album.album_id,album.album_name, concat("uploads/album/",album_image) as album_image,COUNT(recently_view_type_id) as viewCount');
                  $this->db->join('album','album.album_id =recently_view.recently_view_type_id');
             if($is_myProfile)   
                     $this->db->where('recently_view.user_id',$user_id);
                      
                  $this->db->where(array('recently_view_type'=>'Album','album.album_status'=>'ENABLE'));
                  $this->db->group_by('recently_view_type_id');
                  $this->db->order_by('viewCount', 'desc');
                  $this->db->order_by('recently_view.created_date','desc');
             if($start != '' and $end != '')
             {
                 $range = $end-$start; 
           $query=$this->db->get('recently_view', $range,$start);
             }
           else
           $query=$this->db->get('recently_view', $limit);
           $result = $query->result();
           foreach($result as $album){
                $album->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Album','like_type_id'=>$album->album_id))->num_rows() ;
                $album->like_count = $this->db->get_where('liked',array('like_type'=>'Album','like_type_id'=>$album->album_id))->num_rows() ;
                $album->music_count = $this->db->get_where('music',array('album_id' => $album->album_id,'music_status'=>'ENABLE'))->num_rows() ;
           }
          break;
          
          
      case 'favourite_artists':
          $this->db->select('artist.artist_id,artist.artist_name, concat("uploads/artist/",artist_image) as artist_image,COUNT(like_type_id) as likedCount');
                  $this->db->join('artist','artist.artist_id =liked.like_type_id');
             if($is_myProfile)   
                     $this->db->where('liked.user_id',$user_id);
                      
                  $this->db->where(array('like_type'=>'Artist','artist.artist_status'=>'ENABLE'));
                  $this->db->group_by('like_type_id');
                  $this->db->order_by('likedCount', 'desc');
                  $this->db->order_by('liked.created_date','desc');
             if($start != '' and $end != '')
             {
                 $range = $end-$start; 
           $query=$this->db->get('liked', $range,$start);
             }
           else
           $query=$this->db->get('liked', $limit);
           $result = $query->result();
           foreach($result as $artist){
                $artist->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Artist','like_type_id'=>$artist->artist_id))->num_rows() ;
           }
          break;
      
         
      case 'recommended_music':
          $this->db->select('music.music_id,music.artist_id,music.album_id,music.movie_id,music.category_id,music.music_title,concat("uploads/music/",music_file) as music_file, concat("uploads/music/image/",music_image) as music_image,music_duration,COUNT(like_type_id) as likedCount');
                      $this->db->join('music','music.music_id =liked.like_type_id');
              if($is_myProfile)   
                       $this->db->where('liked.user_id',$user_id);
                        
                      $this->db->where(array('like_type'=>'Music','music.music_status'=>'ENABLE'));
                      $this->db->group_by('like_type_id');
                      $this->db->order_by('likedCount', 'desc');
                      $this->db->order_by('liked.created_date','desc');
               if($start != '' and $end != '')
               {
                   $range = $end-$start; 
               $query=$this->db->get('liked', $range,$start);
               }
               else
               $query=$this->db->get('liked', $limit);
               $result = $query->result();
               foreach($result as $music){
                   $music->album_name = ( isset($music->album_id) && ($music->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$music->album_id))->get('album')->row()->album_name) : '' ;
                   $music->category_name = ( isset($music->category_id) && ($music->category_id != 0)) ?  ($this->db->select('category_name')->where(array('category_id'=>$music->category_id))->get('category')->row()->category_name) : '' ;
                   $music->movie_name =( isset($music->movie_id) && ($music->movie_id != 0))  ?  ($this->db->select('movie_name')->where(array('movie_id'=>$music->movie_id))->get('movie')->row()->movie_name) : '' ;
                    $music->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                    $music->is_in_playlist = $this->db->get_where('user_playlist_music',array('user_id' => $user_id,'music_id'=>$music->music_id))->num_rows() ;
                    $music->artists=array();
                    $artist_ids = array_filter(explode(',', $music->artist_id));
                    if(!empty($artist_ids)){
                        foreach($artist_ids as $artist_id) {
                            array_push($music->artists,array('artist_id'=>$artist_id,'artist_name'=>$this->db->get_where('artist',array('artist_id'=>$artist_id))->row()->artist_name));
                        }
                    }
               }        
          break;
      case 'recommended_album':
          $this->db->select('album.album_id,album.album_name, concat("uploads/album/",album_image) as album_image,COUNT(like_type_id) as likedCount');
                      $this->db->join('album','album.album_id =liked.like_type_id');
               if($is_myProfile)   
                       $this->db->where('liked.user_id',$user_id);
                        
                      $this->db->where(array('like_type'=>'Album','album.album_status'=>'ENABLE'));
                      $this->db->group_by('like_type_id');
                      $this->db->order_by('likedCount', 'desc');
                      $this->db->order_by('liked.created_date','desc');
               if($start != '' and $end != '')
               {
                   $range = $end-$start; 
               $query=$this->db->get('liked', $range,$start);
               }
               else
               $query=$this->db->get('liked', $limit);
               $result = $query->result();
               foreach($result as $album){
                    $album->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Album','like_type_id'=>$album->album_id))->num_rows() ;
               }
              break;
      
      case 'recommended_movies':
          $this->db->select('movie.movie_id,movie.movie_name, concat("uploads/movie/",movie_image) as movie_image,COUNT(like_type_id) as likedCount');
                      $this->db->join('movie','movie.movie_id =liked.like_type_id');
               if($is_myProfile)   
                       $this->db->where('liked.user_id',$user_id);
                        
                      $this->db->where(array('like_type'=>'Movie','movie.movie_status'=>'ENABLE'));
                      $this->db->group_by('like_type_id');
                      $this->db->order_by('likedCount', 'desc');
                      $this->db->order_by('liked.created_date','desc');
               if($start != '' and $end != '')
               {
                   $range = $end-$start; 
               $query=$this->db->get('liked', $range,$start);
               }
               else
               $query=$this->db->get('liked', $limit);
               $result = $query->result();
               foreach($result as $movie){
                    $movie->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Movie','like_type_id'=>$movie->movie_id))->num_rows() ;
               }
              
          break;
          
      case 'popular_movies':
          $this->db->select('movie.movie_id,movie.movie_name, concat("uploads/movie/",movie_image) as movie_image,COUNT(recently_view_type_id) as viewCount');
                      $this->db->join('movie','movie.movie_id =recently_view.recently_view_type_id');
               if($is_myProfile)   
                       $this->db->where('recently_view.user_id',$user_id);
                        
                      $this->db->where(array('recently_view_type'=>'Movie','movie.movie_status'=>'ENABLE'));
                      $this->db->group_by('recently_view_type_id');
                      $this->db->order_by('viewCount', 'desc');
                      $this->db->order_by('recently_view.created_date','desc');
               if($start != '' and $end != '')
               {
                   $range = $end-$start; 
               $query=$this->db->get('recently_view', $range,$start);
               }
               else
               $query=$this->db->get('recently_view', $limit);
               $result = $query->result();
               foreach($result as $movie){
                    $movie->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Movie','like_type_id'=>$movie->movie_id))->num_rows() ;
               }
              break;
          
      case 'categories':
                $result = $this->db->select('category_id,category_name')->where(array('parent_category_id'=>0,'category_status'=>'ENABLE'))->order_by('rand()')->get('category',$limit)->result();
                
          break;
          
       default : 
          $result= array();
        }
     
    
        }
      echo json_encode($result);
    }
    
    public function playMusic(){
        $music_id = $this->input->post('music_id');
        $user_id = $this->input->post('user_id');
        $result = $this->db->select('music.*,concat("uploads/music/",music_file) as music_file, concat("uploads/music/image/",music_image) as music_image,music_duration')->where(array('music.music_status'=>'ENABLE','music_id'=>$music_id))->get('music')->row();
        $result->is_in_playlist = $this->db->get_where('user_playlist_music',array('user_id' => $user_id,'music_id'=>$result->music_id))->num_rows() ;
        $result->like_count = $this->likeCount('Music',$result->music_id);
        $result->artists=array();
        $artist_ids = array_filter(explode(',', $result->artist_id));
        foreach($artist_ids as $artist_id) {
            array_push($result->artists,array('artist_id'=>$artist_id,'artist_name'=>$this->db->get_where('artist',array('artist_id'=>$artist_id))->row()->artist_name));
        }
        
          
        $result->album_name = ( isset($result->album_id) && ($result->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$result->album_id))->get('album')->row()->album_name) : '' ;
          
        $result->category_name = ( isset($result->category_id) && ($result->category_id != 0)) ?  ($this->db->select('category_name')->where(array('category_id'=>$result->category_id))->get('category')->row()->category_name) : '' ;
          
        $result->movie_name = ( isset($result->movie_id) && ($result->movie_id != 0)) ?  ($this->db->select('movie_name')->where(array('movie_id'=>$result->movie_id))->get('movie')->row()->movie_name) : '' ;
          
        $result->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Music','like_type_id'=>$music_id))->num_rows() ;
            
        $data =array('recently_view_type'=>'Music','recently_view_type_id'=>$music_id,'user_id'=>$user_id,'created_date'=>date('Y-m-d H:i:s'));
        $this->db->insert('recently_view',$data);

        
        echo json_encode($result);
        
    }
    
    public function viewAlbum(){
        $album_id = $this->input->post('album_id');
        $user_id = $this->input->post('user_id');
        
        $result = $this->db->select('album.*, concat("uploads/album/",album_image) as album_image')->where(array('album.album_status'=>'ENABLE','album_id'=>$album_id))->get('album')->row();
        $result->like_count = $this->likeCount('Album',$result->album_id);
        
          
        $result->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Album','like_type_id'=>$album_id))->num_rows() ; 
         
        $result->music_list = $this->db->select('music.music_id,music.artist_id,music.album_id,music.movie_id,music.category_id,music.music_title,concat("uploads/music/",music_file) as music_file,concat("uploads/music/image/",music_image) as music_image,music_duration')->where(array('music_status'=>'ENABLE','album_id'=>$result->album_id))->get('music')->result();  
        foreach($result->music_list as $music){
                $music->category_name = ( isset($music->category_id) && ($music->category_id != 0)) ?  ($this->db->select('category_name')->where(array('category_id'=>$music->category_id))->get('category')->row()->category_name) : '' ;
                
                $music->album_name = ( isset($music->album_id) && ($music->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$music->album_id))->get('album')->row()->album_name) : '' ;
              
                $music->movie_name =( isset($music->movie_id) && ($music->movie_id != 0))  ?  ($this->db->select('movie_name')->where(array('movie_id'=>$music->movie_id))->get('movie')->row()->movie_name) : '' ;
                
                $music->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                $music->is_in_playlist = $this->db->get_where('user_playlist_music',array('user_id' => $user_id,'music_id'=>$music->music_id))->num_rows() ;
                $music->like_count = $this->db->get_where('liked',array('like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                
                $music->album_name = ( isset($music->album_id) && ($music->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$music->album_id))->get('album')->row()->album_name) : '' ;
                $music->artists=array();
                    $artist_ids = array_filter(explode(',', $music->artist_id));
                    if(!empty($artist_ids)){
                        foreach($artist_ids as $artist_id) {
                            array_push($music->artists,array('artist_id'=>$artist_id,'artist_name'=>$this->db->get_where('artist',array('artist_id'=>$artist_id))->row()->artist_name));
                        }
                    }
          
           }
        
        $data =array('recently_view_type'=>'Album','recently_view_type_id'=>$album_id,'user_id'=>$user_id,'created_date'=>date('Y-m-d H:i:s'));
        
        foreach($result->music_list as $music){
            $music->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows();
            
            $music->like_count = $this->likeCount('Music',$music->music_id);
            
        }
        
        $this->db->insert('recently_view',$data);

        echo json_encode($result);
        
        
    }
    
    
     public function viewMovie(){
        $movie_id = $this->input->post('movie_id');
        $user_id = $this->input->post('user_id');
        
        $result = $this->db->select('movie.*, concat("uploads/movie/",movie_image) as movie_image')->where(array('movie.movie_status'=>'ENABLE','movie_id'=>$movie_id))->get('movie')->row();
        $result->like_count = $this->likeCount('Movie',$result->movie_id);
        
        $result->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Movie','like_type_id'=>$movie_id))->num_rows() ; 
         
        $result->music_list = $this->db->select('music.music_id,music.artist_id,music.album_id,music.movie_id,music.category_id,music.music_title,concat("uploads/music/",music_file) as music_file,concat("uploads/music/image/",music_image) as music_image,music_duration')->where(array('music_status'=>'ENABLE','movie_id'=>$result->movie_id))->get('music')->result();  
        $data =array('recently_view_type'=>'Movie','recently_view_type_id'=>$movie_id,'user_id'=>$user_id,'created_date'=>date('Y-m-d H:i:s'));
        
        foreach($result->music_list as $music){
                $music->category_name = ( isset($music->category_id) && ($music->category_id != 0)) ?  ($this->db->select('category_name')->where(array('category_id'=>$music->category_id))->get('category')->row()->category_name) : '' ;
                
                $music->album_name = ( isset($music->album_id) && ($music->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$music->album_id))->get('album')->row()->album_name) : '' ;
              
                $music->movie_name =( isset($music->movie_id) && ($music->movie_id != 0))  ?  ($this->db->select('movie_name')->where(array('movie_id'=>$music->movie_id))->get('movie')->row()->movie_name) : '' ;
            $music->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows();
             $music->is_in_playlist = $this->db->get_where('user_playlist_music',array('user_id' => $user_id,'music_id'=>$music->music_id))->num_rows() ;
            $music->like_count = $this->likeCount('Music',$music->music_id);
            $music->album_name = ( isset($music->album_id) && ($music->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$music->album_id))->get('album')->row()->album_name) : '' ;
                $music->artists=array();
                    $artist_ids = array_filter(explode(',', $music->artist_id));
                    if(!empty($artist_ids)){
                        foreach($artist_ids as $artist_id) {
                            array_push($music->artists,array('artist_id'=>$artist_id,'artist_name'=>$this->db->get_where('artist',array('artist_id'=>$artist_id))->row()->artist_name));
                        }
                    }
          
            
        }
        
        $this->db->insert('recently_view',$data);

        echo json_encode($result);
        
        
    }
    
    public function viewArtist(){
        $artist_id = $this->input->post('artist_id');
        $user_id = $this->input->post('user_id');
        
        $result = $this->db->select('artist.*, concat("uploads/artist/",artist_image) as artist_image')->where(array('artist.artist_status'=>'ENABLE','artist_id'=>$artist_id))->get('artist')->row();
        $result->like_count = $this->likeCount('Artist',$result->artist_id);
        
        
        $result->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Artist','like_type_id'=>$artist_id))->num_rows() ; 
         
        $result->music_list = $this->db->select('music.music_id,music.artist_id,music.album_id,music.movie_id,music.category_id,music.music_title,concat("uploads/music/",music_file) as music_file,concat("uploads/music/image/",music_image) as music_image,music_duration')->where(array('music_status'=>'ENABLE','artist_id'=>$result->artist_id))->get('music')->result();  
        foreach($result->music_list as $music){
                $music->category_name = ( isset($music->category_id) && ($music->category_id != 0)) ?  ($this->db->select('category_name')->where(array('category_id'=>$music->category_id))->get('category')->row()->category_name) : '' ;
                
                $music->album_name = ( isset($music->album_id) && ($music->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$music->album_id))->get('album')->row()->album_name) : '' ;
              
                $music->movie_name =( isset($music->movie_id) && ($music->movie_id != 0))  ?  ($this->db->select('movie_name')->where(array('movie_id'=>$music->movie_id))->get('movie')->row()->movie_name) : '' ;
          
                $music->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                $music->is_in_playlist = $this->db->get_where('user_playlist_music',array('user_id' => $user_id,'music_id'=>$music->music_id))->num_rows() ;
                $music->like_count = $this->db->get_where('liked',array('like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                
                
                $music->artists=array();
                    $artist_ids = array_filter(explode(',', $music->artist_id));
                    if(!empty($artist_ids)){
                        foreach($artist_ids as $artist_id) {
                            array_push($music->artists,array('artist_id'=>$artist_id,'artist_name'=>$this->db->get_where('artist',array('artist_id'=>$artist_id))->row()->artist_name));
                        }
                    }
          
           }
        
        $data =array('recently_view_type'=>'Artist','recently_view_type_id'=>$artist_id,'user_id'=>$user_id,'created_date'=>date('Y-m-d H:i:s'));
        
        foreach($result->music_list as $music){
            $music->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows();
            
            $music->like_count = $this->likeCount('Music',$music->music_id);
            
        }
        
        $this->db->insert('recently_view',$data);

        echo json_encode($result);
        
        
    }
    
    public function like()
    {   
          
            $data=array(
                'user_id' => $this->input->post('user_id'),
                'like_type' => $this->input->post('like_type'),
                'like_type_id' => $this->input->post('like_type_id'),
                'like_date' => date('Y-m-d'),
                'created_date' =>date('Y-m-d H:i:s')
              );
            $result= $this->db->get_where('liked',array('user_id' => $data['user_id'],'like_type'=>$data['like_type'],'like_type_id'=>$data['like_type_id']))->result() ;
            if(empty($result)) $this->db->insert('liked',$data); 
            echo json_encode(array());
       
    }
    
    public function unlike()
    {   
         
            $data=array(
                'user_id' => $this->input->post('user_id'),
                'like_type' => $this->input->post('like_type'),
                'like_type_id' => $this->input->post('like_type_id'),
              );
            $result =  $this->db->where($data)->delete('liked'); 
             echo json_encode(array());
            
    }
    
    public function createPlayList(){
        $user_id = $this->input->post('user_id');
        $user_playlist_name = $this->input->post('user_playlist_name');
        
        
         $result= $this->db->get_where('user_playlist',array('user_id' => $user_id,'user_playlist_name'=>$user_playlist_name))->result() ;
            if(empty($result))
            {   
                $data = array(
                        'user_id' => $user_id,
                        'user_playlist_name'=>$user_playlist_name,
                        'created_date'=>date('Y-m-d H:i:s')
                        
                    );
                $this->db->insert('user_playlist',$data); 
            }
            echo json_encode(array());
        
 
    }
    
    public function getPlaylists(){
        
        $user_id = $this->input->post('user_id');
        $result = $this->db->select('user_playlist_id,user_playlist_name')->where(array('user_id'=>$user_id))->order_by('created_date','DESC')->get('user_playlist')->result();
       foreach($result as $playlist){
           
           $playlist->music_count = $this->db->select('user_playlist_music.*')->join('music', 'music.music_id=user_playlist_music.music_id')->where(array('user_id'=>$user_id,'user_playlist_id'=>$playlist->user_playlist_id,'music_status'=>'ENABLE'))->get('user_playlist_music')->num_rows();
           
            $playlist->images = $this->db->select('concat("uploads/music/image/",music_image) as music_image')->join('user_playlist_music','user_playlist.user_playlist_id = user_playlist_music.user_playlist_id')->join('music','music.music_id = user_playlist_music.music_id')->where(array('user_playlist_music.user_id'=>$user_id,'user_playlist_music.user_playlist_id'=>$playlist->user_playlist_id,'music_status'=>'ENABLE'))->order_by('rand()')->get('user_playlist',3)->result();
       }
       
       
        
        echo json_encode($result);

    }
    
    public function addInPlaylist(){
        $data=array(
            'user_id' => $this->input->post('user_id'),
            'music_id' => $this->input->post('music_id'),
            'user_playlist_id' => $this->input->post('user_playlist_id')
         );
         $result =  $this->db->get_where('user_playlist_music',array('user_id' => $data['user_id'],'music_id' => $data['music_id'],'user_playlist_id' => $data['user_playlist_id']))->result() ;
        if(empty($result))
        {
            $data['created_date']= date("Y-m-d H:i:s");
            $this->db->insert('user_playlist_music',$data); 
        }
        echo json_encode(array()); 
    }
    
    public function removeFromPlaylist(){
        $data=array(
            'user_id' => $this->input->post('user_id'),
            'music_id' => $this->input->post('music_id')
         );
        $result =  $this->db->where($data)->delete('user_playlist_music'); 
        
        echo json_encode(array()); 
    }
    
    public function getPlaylistMusic(){
        $data=array(
            'user_id' => $this->input->post('user_id'),
            'user_playlist_id' => $this->input->post('user_playlist_id')
         );
        $start =($this->input->post('start') ? $this->input->post('start') : 0);
            $end =($this->input->post('end') ? $this->input->post('end') : 10);
        $range = $end-$start;
        
              $this->db->select('music.music_id,music.artist_id,music.album_id,music.movie_id,music.category_id,music.music_title,concat("uploads/music/",music_file) as music_file, concat("uploads/music/image/",music_image) as music_image');
              $this->db->join('music','music.music_id =user_playlist_music.music_id');
              
              $this->db->where(array('user_playlist_music.user_playlist_id'=>$data['user_playlist_id'],'music.music_status'=>'ENABLE'));
             
              $this->db->order_by('user_playlist_music.created_date','desc');
               
              $query=$this->db->get('user_playlist_music', $range,$start);
               
               
               $result = $query->result();
               
                foreach($result as $music){
                    $music->album_name = ( isset($music->album_id) && ($music->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$music->album_id))->get('album')->row()->album_name) : '' ;
                   $music->category_name = ( isset($music->category_id) && ($music->category_id != 0)) ?  ($this->db->select('category_name')->where(array('category_id'=>$music->category_id))->get('category')->row()->category_name) : '' ;
                   $music->movie_name =( isset($music->movie_id) && ($music->movie_id != 0))  ?  ($this->db->select('movie_name')->where(array('movie_id'=>$music->movie_id))->get('movie')->row()->movie_name) : '' ;
                    $music->is_liked = $this->db->get_where('liked',array('user_id' => $data['user_id'],'like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                  $music->like_count = $this->likeCount('Music',$music->music_id);
                  
                    $music->artists=array();
                    $artist_ids = array_filter(explode(',', $music->artist_id));
                    if(!empty($artist_ids)){
                        foreach($artist_ids as $artist_id) {
                            array_push($music->artists,array('artist_id'=>$artist_id,'artist_name'=>$this->db->get_where('artist',array('artist_id'=>$artist_id))->row()->artist_name));
                        }
                    }
            
               }  
         echo json_encode($result);
         
    }
    
    public function deletePlayList(){
        $data=array(
            'user_id' => $this->input->post('user_id'),
            'user_playlist_id' => $this->input->post('user_playlist_id')
         );
         
        $result =  $this->db->where($data)->delete('user_playlist_music'); 
        $result1=  $this->db->where($data)->delete('user_playlist'); 
             echo json_encode(array());
        
    }
    
    public function getPackages(){
        echo json_encode($this->db->select('package_id,package_name,package_duration,concat("$",package_price)  as package_price,concat("$",(package_price * package_duration))  as total_package_price')->where(array('package_status'=>"ENABLE",'package_name !='=>'Free'))->get('package_settings')->result());
        
    }
    
    public function getPaymentMethods(){
        echo json_encode($this->db->select('*')->where('payment_method_status',"ENABLE")->get('payment_method')->result());
        
    }
    
    public function getStripePaymentScreen(){
        
       
        $data['total_package_price'] = $this->input->post('total_package_price');
        $data['package_id'] = $this->input->post('package_id');
        $data['user_id'] = $this->input->post('user_id');
        
        $this->load->view('stripe_payment',$data);
    }
    
    
    
    public function search(){
        $search = $this->input->post('search');
        
        $result = array();
        
       $data1  = $this->db->select('music_id as id,music_title as search_text')->where("music_title like TRIM('%$search%')")->get('music')->result();
       foreach($data1 as $data){
           
           array_push($result,array('search_type'=>'music','id'=>$data->id,'search_text'=>$data->search_text));
       }
       
       
       $data2  = $this->db->select('album_id as id,album_name as search_text')->where("album_name like TRIM('%$search%')")->get('album')->result();
       foreach($data2 as $data){
           array_push($result,array('search_type'=>'album','id'=>$data->id,'search_text'=>$data->search_text));
       }
       
       $data3  = $this->db->select('artist_id as id,artist_name as search_text')->where("artist_name like TRIM('%$search%')")->get('artist')->result();
       foreach($data3 as $data){
           array_push($result,array('search_type'=>'artist','id'=>$data->id,'search_text'=>$data->search_text));
       }
       
       $data4  = $this->db->select('movie_id as id,movie_name as search_text')->where("movie_name like TRIM('%$search%')")->get('movie')->result();
       foreach($data4 as $data){
           array_push($result,array('search_type'=>'movie','id'=>$data->id,'search_text'=>$data->search_text));
       }
       
        echo json_encode($result);
    }
    
    public function getAllMusics(){
        $user_id = $this->input->post('user_id');
        $start =($this->input->post('start') ? $this->input->post('start') : 0);
        $end =($this->input->post('end') ? $this->input->post('end') : 10);
        
        $range = $end - $start;
                $this->db->select('music_id,artist_id,album_id,movie_id,category_id,music_title,concat("uploads/music/",music_file) as music_file,concat("uploads/music/image/",music_image) as music_image,music_duration');
                $this->db->where(array('music_status'=>'ENABLE'));
                $this->db->order_by('music.created_date','desc');
            $query=$this->db->get('music', $range,$start);
            
            $result = $query->result();
            
            foreach($result as $music){
                
                $music->album_name = ( isset($music->album_id) && ($music->album_id != 0)) ?  ($this->db->select('album_name')->where(array('album_id'=>$music->album_id))->get('album')->row()->album_name) : '' ;
               $music->category_name = ( isset($music->category_id) && ($music->category_id != 0)) ?  ($this->db->select('category_name')->where(array('category_id'=>$music->category_id))->get('category')->row()->category_name) : '' ;
               $music->movie_name =( isset($music->movie_id) && ($music->movie_id != 0))  ?  ($this->db->select('movie_name')->where(array('movie_id'=>$music->movie_id))->get('movie')->row()->movie_name) : '' ;
                $music->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                $music->is_in_playlist = $this->db->get_where('user_playlist_music',array('user_id' => $user_id,'music_id'=>$music->music_id))->num_rows() ;
                $music->like_count = $this->db->get_where('liked',array('like_type'=>'Music','like_type_id'=>$music->music_id))->num_rows() ;
                
                $music->artists=array();
                    $artist_ids = array_filter(explode(',', $music->artist_id));
                    if(!empty($artist_ids)){
                        foreach($artist_ids as $artist_id) {
                            array_push($music->artists,array('artist_id'=>$artist_id,'artist_name'=>$this->db->get_where('artist',array('artist_id'=>$artist_id))->row()->artist_name));
                        }
                    }
            }
            
            echo json_encode($result);
    }
    
    public function getAllArtists(){
        
        $user_id = $this->input->post('user_id');
        $start =($this->input->post('start') ? $this->input->post('start') : 0);
        $end =($this->input->post('end') ? $this->input->post('end') : 10);
        $range = $end-$start; 
        
            $this->db->select('artist_id,artist_name, concat("uploads/artist/",artist_image) as artist_image');
            $this->db->order_by('created_date','desc');
                 
                 
           $query=$this->db->get('artist', $range,$start);
            
            $result = $query->result();
            foreach($result as $artist){
                $artist->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Artist','like_type_id'=>$artist->artist_id))->num_rows() ;
                
                $artist->like_count = $this->db->get_where('liked',array('like_type'=>'Artist','like_type_id'=>$artist->artist_id))->num_rows() ;
           }
           
        echo json_encode($result);
    }
    
    public function getAllAlbums(){
        
        $user_id = $this->input->post('user_id');
        $start =($this->input->post('start') ? $this->input->post('start') : 0);
        $end =($this->input->post('end') ? $this->input->post('end') : 10);
        $range = $end-$start; 
        
            $this->db->select('album_id,album_name, concat("uploads/album/",album_image) as album_image');
            $this->db->order_by('created_date','desc');
                 
                 
           $query=$this->db->get('album', $range,$start);
            
            $result = $query->result();
            foreach($result as $album){
                $album->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Album','like_type_id'=>$album->album_id))->num_rows() ;
                
                $album->like_count = $this->db->get_where('liked',array('like_type'=>'Album','like_type_id'=>$album->album_id))->num_rows() ;
           }
           
        echo json_encode($result);
    }
    
    public function getAllMovies(){
        
        $user_id = $this->input->post('user_id');
        $start =($this->input->post('start') ? $this->input->post('start') : 0);
        $end =($this->input->post('end') ? $this->input->post('end') : 10);
        $range = $end-$start; 
        
            $this->db->select('movie_id,movie_name, concat("uploads/movie/",movie_image) as movie_image');
            $this->db->order_by('created_date','desc');
                 
                 
           $query=$this->db->get('movie', $range,$start);
            
            $result = $query->result();
            foreach($result as $movie){
                $movie->is_liked = $this->db->get_where('liked',array('user_id' => $user_id,'like_type'=>'Movie','like_type_id'=>$movie->movie_id))->num_rows() ;
                
                $movie->like_count = $this->db->get_where('liked',array('like_type'=>'Movie','like_type_id'=>$movie->movie_id))->num_rows() ;
           }
           
        echo json_encode($result);
    }
    
    public function likeCount($like_type='',$id=0){
        
        if($like_type!='' && $id!=0)
            return $db_result = $this->db->get_where('liked',array('like_type' => $like_type, 'like_type_id' => $id))->num_rows();
        else
            return 0;
    }
    
    
    public function getAllCategories()
    {
            $result = $this->db->get_where('category', array(
                'parent_category_id'=>0,'category_status'=>'ENABLE'))->result();
                
            if(!empty($result)){
                $i=0;
               foreach($result as $category){
                    $result[$i]->subcategory = $this->subCategories($category->category_id);
                    $i++;
                } 
                
            }
            echo json_encode($result);
    }
    
  public function getCategoryMusic(){
        $user_id = $this->input->post('user_id');
        $category_id = $this->input->post('category_id');
        $start =($this->input->post('start') ? $this->input->post('start') : '');
        $end =($this->input->post('end') ? $this->input->post('end') : '');
        
        $result = $this->db->select('*,concat("uploads/music/",music_file) as music_file, concat("uploads/music/image/",music_image) as music_image,music_duration')->where(array(
                'category_id'=>$category_id,'music_status'=>'ENABLE') )->get('music')->result(); 
       
        echo json_encode($result);

  }
  
  public function isAllowDownloads(){
      $user_id = $this->input->post('user_id');
      $allow_downloads = 0;
       $result = $this->db->get_where('user', array('user_id'=>$user_id))->result();
         if(!empty($result)){
             $is_package_enable = $this->db->select('settings_flag_value')->where('settings_flag_name','Package')->get('settings_flag')->row()->settings_flag_value;
            if($is_package_enable == 'ENABLE') {
                 $date_expire = strtotime($result[0]->user_package_expiry_date);
                 $date_current = strtotime(date('Y-m-d H:i:s'));
                if($date_current >= $date_expire)
                    $allow_downloads = 0;
                else
                    $allow_downloads = 1;
            }
            else{
                $allow_downloads = 1;
            }
             
         }
        echo json_encode(array('is_allow_downloads'=>$allow_downloads)); 
         
  }
  
  public function getUserPackageInfo(){
       $user_id = $this->input->post('user_id');
       $result = $this->db->select('user_package_id,package_name,user_package_paid_date,user_package_expiry_date')->join('package_settings','user.user_package_id = package_settings.package_id')->where( array('user_id'=>$user_id))->get('user')->result();
       
       echo json_encode($result);
  }
    
   public function subCategories($category_id){

        $db_result = $this->db->get_where('category', array(
                'parent_category_id'=>$category_id,'category_status'=>'ENABLE'))->result();
        $i=0;
        foreach($db_result as $subcategory){
            $db_result[$i]->subcategory = $this->subCategories($subcategory->category_id);
            $i++;
        }
        return $db_result;       
    }
    
 
    
}
            