<?php 
class WCSTS_Product 
{
	public function __construct()
	{
	}
	public function update_meta($product_id, $key, $value)
	{
		$product = wc_get_product($product_id);
		$product->update_meta_data($key, $value);
		$product->save();
	}
	public function get_meta($product_id, $key, $single = true)
	{
		$product = wc_get_product($product_id);
		return $product ? $product->get_meta($key, $single) : "";
	}
	public function update_product_questions_number($product_id, $number)
	{
		$this->update_meta($product_id, '_wcsts_ppt_questions_number', $number);
	}
	public function get_product_questions_number($product_id)
	{
		$number = $this->get_meta($product_id, '_wcsts_ppt_questions_number');
		return $number ? $number : 0;
	}
	public function get_variation_complete_name($variation_id)
	 {
		$error = false;
		$variation = wc_get_product($variation_id);
		//wcqpe_var_dump("inside ".$variation->get_type());
		if($variation == null || $variation == false)
			return "";
		if($variation->is_type('simple') || $variation->is_type('variable'))
			return $variation->get_title();
		
		$product_name = $variation->get_title()." - ";	
		if($product_name == " - ")
			return false;
		$attributes_counter = 0;
		foreach($variation->get_variation_attributes( ) as $attribute_name => $value)
		{
			
			if($attributes_counter > 0)
				$product_name .= ", ";
			$meta_key = urldecode( str_replace( 'attribute_', '', $attribute_name ) ); 
			//wcqpe_var_dump($value);
			$product_name .= " ".wc_attribute_label($meta_key).": ".$value;
			$attributes_counter++;
		}
		return $product_name;
	 }
} 
?>