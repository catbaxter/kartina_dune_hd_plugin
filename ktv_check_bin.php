<?php

class CHECK
{

    public static function check_for_arch()
    {
        $plugin_dir = DuneSystem::$properties['install_dir_path'];
        if (!file_exists('/tmp/arch_kart/msdl'))
        {
            system("mkdir /tmp/arch_kart",$return_var);
            system("cp $plugin_dir/bin/msdl /tmp/arch_kart/msdl",$return_var);
            system("chmod +x /tmp/arch_kart/msdl", $return_var);
        }
        if (!file_exists('/tmp/arch_kart/rec'))
        {
            system("mkdir /tmp/arch_kart",$return_var);
            system("cp $plugin_dir/bin/rec /tmp/arch_kart/rec",$return_var);
            system("chmod +x /tmp/arch_kart/rec", $return_var);
        }
        if (!file_exists('/tmp/arch_kart/cgi-bin/arch'))
        {
            system("mkdir /tmp/arch_kart/cgi-bin",$return_var);
            system("cp $plugin_dir/bin/arch /tmp/arch_kart/cgi-bin/arch",$return_var);
            system("chmod +x /tmp/arch_kart/cgi-bin/arch", $return_var);
        }
        $hh = system('ps | grep httpd | grep -c /tmp/arch_kart',$return_var);
        hd_print("var httpd:  $hh");
        if ( $hh <= 1)
        {
            system("httpd -h /tmp/arch_kart -p 1000",$return_var);
        }
        return true;
    }
}

?>