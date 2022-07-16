<?php 
 function thumb($fullname, $width, $height)
    {
        // Path to image thumbnail in your root
        $dir = './path/to/uploads/';
        $url = base_url() . 'path/to/uploads/';
        // Get the CodeIgniter super object
        // $CI = &get_instance();
        // get src file's extension and file name
        $extension = pathinfo($fullname, PATHINFO_EXTENSION);
        $filename = pathinfo($fullname, PATHINFO_FILENAME);
        $image_org = $dir . $filename . "." . $extension;
        $image_thumb = $dir . $filename . "-" . $height . '_' . $width . "." . $extension;
        $image_returned = $url . $filename . "-" . $height . '_' . $width . "." . $extension;

        if (!file_exists($image_thumb)) {
            // LOAD LIBRARY
            $this->load->library('image_lib');
            // CONFIGURE IMAGE LIBRARY
            $config['source_image'] = $image_org;
            $config['new_image'] = $image_thumb;
            $config['width'] = $width;
            $config['height'] = $height;
            $this->image_lib->initialize($config);
            $this->image_lib->resize();
            $this->image_lib->clear();
        }
        return $image_returned;
    }

    ?>