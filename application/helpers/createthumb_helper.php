<?php 
 function thumb($dir,$filename, $width, $height)
    {
        // Path to image thumbnail in your root
        // $dir = './path/to/uploads/';
        $url =  base_url().$dir;
        // Get the CodeIgniter super object
        $CI = &get_instance();
        // get src file's extension and file name
        // $extension = pathinfo($fname, PATHINFO_EXTENSION);
        // $filename = pathinfo($fname, PATHINFO_FILENAME);
        $image_org = $dir . $filename ;
        $image_org_returned = $url . $filename ;

        if (!file_exists($dir.'/thumb')) {
            mkdir($dir.'/thumb', 0777, true);
        }

        $image_thumb = $dir.'thumb/' . $filename ;
        $image_returned = $url.'thumb/' . $filename  ;

        // if (!file_exists($image_thumb)) {
            
            
            
            $CI->load->library('image_lib');
            $config['image_library']  = 'gd2';
            $config['source_image']   = $image_org;       
            $config['maintain_ratio'] = TRUE;
            $config['width']          = $width;
            $config['height']         = $height;
            $config['overwrite'] = TRUE;
            $config['new_image']      = $image_thumb;               
            $CI->image_lib->initialize($config);
            if (! $CI->image_lib->resize()) { 
                return $image_org_returned;
            }        
        // }
        return $image_returned;
    }

    ?>