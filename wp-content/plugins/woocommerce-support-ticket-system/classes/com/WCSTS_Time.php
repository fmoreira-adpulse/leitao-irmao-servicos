<?php 
class WCSTS_Time
{
	public function __construct()
	{
	
	}
	public function can_be_posted($date, $interval)
	{
		$date_now = new DateTime(current_time('Y-m-d H:i:s'));
		$ticket_date = new DateTime($date);
		$ticket_date->modify('+'.$interval.' seconds');
		
		return $ticket_date < $date_now;
	}
	public function get_date_time_according_wordpress_settings($datetime)
	{
		if($datetime == "" || !isset($datetime))
			return "";
		
		$date = new \DateTime($datetime);
		return $date->format(get_option('date_format')." ".get_option('time_format'));
	}
}
?>