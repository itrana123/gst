<?php
	

class hotwire_lib{
	private $API_KEY	= "b8aahqackupgs6acbwj6pmpq";

	public function __construct(){
		$this->CI =& get_instance();
	}

	
	function get_hotel($city=false,$checkIn=false,$checkOut,$adult,$children,$IP) {
		$result  = array();
		$result['status'] = 'error';
		$pro =array();

		$id = $this->get_location($city,true);
		if($id=='0'){
			$id = $this->get_location_g($city,true);
		}
		else{
		}

/*		$params = http_build_query(array(
			'apikey' =>$this->API_KEY,
			"dest" =>'IDR',
			"startdate" => '05/19/2017',
			"enddate" =>'05/20/2017',
			"rooms" => '1',
			"children" =>'0',
			"adults" => '1',
		));
*/		
		$params = http_build_query(array(
			'apikey' =>$this->API_KEY,
			"dest" =>$id,
			"startdate" => h_dateFormat($checkIn,'m/d/Y'),
			"enddate" =>h_dateFormat($checkOut,'m/d/Y'),
			"rooms" => '1',
			"children" =>$children,
			"adults" => $adult,
		));
		

		$url = 'http://api.hotwire.com/v1/search/hotel?'.$params;
		$cSession = curl_init(); 
		//step2
		curl_setopt($cSession,CURLOPT_URL,$url);
		curl_setopt($cSession, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($cSession, CURLOPT_HEADER, FALSE);
	
		$result_data = curl_exec($cSession);
		curl_close($cSession);

		$result_data = str_replace(array("\n", "\r", "\t"), '', $result_data);
		$result_data = trim(str_replace('"', "'", $result_data));
		$xml = simplexml_load_string($result_data);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);
/*		echo '<pre>';
		print_r($array);*/
		//echo '<pre>';
		
		if($array&&isset($array['Result']['HotelResult'])&&!empty($array['Result']['HotelResult'])){
			$result['status'] = 'ok';
			$tmp = $array['Result']['HotelResult'];
//			echo '<pre>';
/*			print_r($tmp);
*/
				//echo $result['status'];
			foreach($tmp as $set_p){
				$temp =array();
				$temp['id'] = $set_p['HWRefNumber'];
				$temp['link'] = $set_p['DeepLink'];
				$temp['stars'] =  round($set_p['StarRating']);
				$temp['rating'] =  0;
				$temp['type'] = 'hotewire';
				$temp['address'] = '';
				$temp['price'] = $set_p['TotalPrice'];
				$temp['maxPrice'] = 0;
				$temp['name'] = 'Hotewire Hotel';
				$temp['photos'] = '';
				
				$temp['location'] = array('lon'=>'','lat'=>'');
				$pro[] = $temp;
				//print_r($temp);
			}
		}

		
		$result['pro'] = $pro;
/*		echo '<pre>';
		print_r($result);
		die;*/
	    return $result;
		
	}
	
	function get_location($string=false,$show=false){
//		echo $newString =  str_replace("-"," ",$string);

		$ch = curl_init();
		$params = http_build_query(array(
				'limit' =>1,
				'lookFor'=>'city',
				'lang'=>'en',
				'query'	=> $string,
				'token' =>$this->API_KEY
				));
		
		$url ="http://engine.hotellook.com/api/v2/lookup.json?".$params;
		curl_setopt($ch, CURLOPT_URL,$url);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
	//	curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Access-Token:"));
		$response = curl_exec($ch);
		$res= json_decode($response);
		curl_close($ch);
/*		echo '<pre>';
		print_r($res);*/
		
		if($res->status='ok'){
			if($show){
/*				echo 'asd';*/
				if(isset($res->results->locations[0]->iata)&&isset($res->results->locations[0]->iata[0])){
					return $res->results->locations[0]->iata[0];
				}
				
			}
			else{
				if(isset($res->results->locations)&&!empty($res->results->locations)){
					return $res->results->locations[0]->id;
				}
			}
		}
		return 0;	
	}
	
	function get_location_g($dlocation){
     // Get lat and long by address         
        $address = $dlocation; // Google HQ
        $prepAddr = str_replace(' ','+',$address);
		
		$ch = curl_init();
		$params = http_build_query(array(
				'address' =>$prepAddr,
				'sensor'=>'false',
				));
		
		$url ="https://maps.google.com/maps/api/geocode/json?".$params;
		curl_setopt($ch, CURLOPT_URL,$url);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
	//	curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Access-Token:"));
		$response = curl_exec($ch);
		$output = json_decode($response);
		curl_close($ch);

      //  $geocode=file_get_contents($string);
/*		echo '<pre>';
		print_r($output);*/

		if ($output->status == 'OK') {
    	    $latitude = $output->results[0]->geometry->location->lat;
	        $longitude = $output->results[0]->geometry->location->lng;
			$ch = curl_init();	
			$url ="http://iatageo.com/getCode/".$latitude.'/'.$longitude;
			curl_setopt($ch, CURLOPT_URL,$url);
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//	curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Access-Token:"));
			$response = curl_exec($ch);
			$res= json_decode($response);
			curl_close($ch);
/*			echo '<pre>';
			echo $res->IATA;
			print_r($res);*/
			if(!empty($res)&&isset($res->IATA)){
				return $res->IATA;
			}
		}
		return 0;
	}
	
	function get_hotel_data($loc_id,$limit=false){
			$ch = curl_init();
			$params = http_build_query(array(
					'locationId'=>$loc_id,
					'token' =>$this->API_KEY
					));
			$url ='http://engine.hotellook.com/api/v2/static/hotels.json?'.$params;
		//	$url ='http://engine.hotellook.com/api/v2/cache.json?location='.$string.'&lang=en&lookFor=both&limit='.$limit;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			//curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Access-Token:cef8472114e959429c4886a246289a62"));
			$response = curl_exec($ch);
			curl_close($ch);
			
			$res = json_decode($response);
			return $res;
	}

	
	function test_data($city=false,$checkIn=false,$checkOut,$adult,$children,$IP) {
		$result  = array();
		$result['status'] = 'error';
		$pro =array();

		$id = $this->get_location($city,true);
	//	echo $id;die;
		if($id){
//			$searchIDs = $this->get_search_id($id,$adult,$checkIn,$checkOut,$children,'USD',$IP,'en',20,0);
/*			echo '<pre>';
			print_r($searchIDs);
*/
			$searchData = $this->get_search_data(8181671,20,'price');
/*			echo '<pre>';
			print_r($searchData);*/

			if($searchData&&$searchData->status=='ok'){
				$tmp = $searchData->result;
				if(isset($tmp)&&!empty($tmp)){
					$result['status'] = 'ok';
					//echo $result['status'];
					 foreach($tmp as $key=>$set_p){
						$temp =array();
						$temp['id'] = $set_p->id;
						$temp['link'] = $set_p->fullUrl;
						$temp['stars'] =  $set_p->stars;
						$temp['rating'] =  $set_p->rating;
						$temp['type'] = 'travelpayouts';
						$temp['address'] = $set_p->address;
						$temp['price'] = $set_p->price;
						$temp['maxPrice'] = $set_p->maxPrice;
						$temp['name'] = $set_p->name;
						$temp['photos'] = 'http://cdn.photo.hotellook.com/image_v2/crop/h'.$set_p->id.'/640/480.jpg';

						$temp['location'] = array('lon'=>$set_p->location->lon,'lat'=>$set_p->location->lat);
						$pro[] = $temp;
						//print_r($temp);
					 }
				}
			}
		}
		
		
		$result['pro'] = $pro;
/*		echo '<pre>';
		print_r($search_data);
		die;
*/
	    return $result;
	}
	
	function get_search_data($searchId,$limit,$price){
			$ch = curl_init();
			
			$string2 = md5($this->API_KEY.':'.$this->API_Marker.':'.$limit.':0:0:'.$searchId.':1:'.$price);
			$params = http_build_query(array(
				'searchId' 		=> $searchId,
				'limit'			=> $limit,
				'sortBy'		=> $price,
				'sortAsc'		=> 1,
				'roomsCount'	=> 0,
				'offset'		=> 0,
				'marker'		=> $this->API_Marker,
				'signature' 	=> $string2
			));

			//$url ='http://engine.hotellook.com/api/v2/search/getResult.json?searchId='.$searchId.'&limit='.$limit.'&sortBy='.$price.'&sortAsc=1&roomsCount=0&offset=0&marker='.$this->API_Marker.'&signature='.$string2;
			$url ='http://engine.hotellook.com/api/v2/search/getResult.json?'.$params;
			curl_setopt($ch, CURLOPT_URL, $url);
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			//curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Access-Token:cef8472114e959429c4886a246289a62"));
			$response = curl_exec($ch);
			curl_close($ch);
			
			$res = json_decode($response);
			return $res;
	}
	
	function get_search_data2($string,$limit){
			$ch = curl_init();
			$url ='http://engine.hotellook.com/api/v2/lookup.json?query='.$string.'&lang=en&lookFor=both&limit='.$limit.'&token='.$this->API_KEY;
		//	$url ='http://engine.hotellook.com/api/v2/cache.json?location='.$string.'&lang=en&lookFor=both&limit='.$limit;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			//curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Access-Token:cef8472114e959429c4886a246289a62"));
			$response = curl_exec($ch);
			curl_close($ch);
			
			$res = json_decode($response);
			return $res;
	}
	
	function get_search_id($iata,$adult,$checkIn,$checkOut,$chlidrenCount,$currency,$IP,$lang,$timeout,$waitForResult){
//		$iata = 'DWC';
		if($chlidrenCount){
			$chlidrenCount =$chlidrenCount;
		}
		else{
			$chlidrenCount =0;
		}

		//YourToken:YourMarker:adultsCount:checkIn:checkOut:childrenCount:currency:customerIP:iata:lang:timeout:waitForResult
		$hashCode = md5($this->API_KEY.':'.$this->API_Marker.':'.$adult.':'.$checkIn.':'.$checkOut.':'.$chlidrenCount.':'.$currency.':'.$IP.':'.$iata.':'.$lang.':'.$timeout.':'.$waitForResult);

		$string = 'iata='.$iata.'&checkIn='.$checkIn.'&checkOut='.$checkOut.'&adultsCount='.$adult.'&customerIP='.$IP.'&childrenCount='.$chlidrenCount.'&lang='.$lang."&currency=".$currency.'&timeout='.$timeout.'&waitForResult='.$waitForResult.'&marker='.$this->API_Marker.'&signature='.$hashCode;

		$ch = curl_init();

		$url= "http://engine.hotellook.com/api/v2/search/start.json?".$string;
	//	echo '<br><br>'.$url;

/*		echo '<br>'.$hashCode;
		echo '<br>'.$url;
*/
		curl_setopt($ch, CURLOPT_URL, $url);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Access-Token:".$this->API_KEY));
		$response = curl_exec($ch);
	
		curl_close($ch);
		
/*		echo '<pre>';
		print_r($response);
		die;*/
		$res= json_decode($response);
		return $res;

	}



}
?>
