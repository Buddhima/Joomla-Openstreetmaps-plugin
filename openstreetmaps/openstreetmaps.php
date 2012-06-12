<?php
/**
 * @package		Joomla.Plugin
 * @subpackage	Content.openstreetmaps
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

class plgContentOpenstreetmaps extends JPlugin
{

	/**
	 * Plugin that loads openstreetmaps into articles
	 *
	 * @param	string	The context of the content being passed to the plugin.
	 * @param	object	The article object.  Note $article->text is also available
	 * @param	object	The article params
	 * @param	int		The 'page' number
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		// simple check to determine whether should process further
		if (strpos($article->text, 'openstreetmaps') === false) {
			return true;
		}

		// expression to search for (positions)
		$regex	= '/{openstreetmaps\s+(.*?)}/i';

		// Find all instances of plugin and put in $matches for openstreetmaps
		// $matches[0] is full pattern match, $matches[1] is the position
		preg_match_all($regex,$article->text,$matches,PREG_SET_ORDER);

		if($matches){

			// iterate through all matches found 
			foreach($matches as $match){

				// default values
				$this->btmlat =4.59;
				$this->toplat =11.86;
				$this->lftlong =76.68;
				$this->rgtlong=85.09;
				$this->str='';

				// get parameters given by user

				// get latitudes as top and bottom boarders
				if (preg_match('/bottom_latitude=([\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->btmlat = $matches2[1];
				if (preg_match('/top_latitude=([\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->toplat = $matches2[1];

				// get longitudes as laft and right boarders
				if (preg_match('/left_longitude=([\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->lftlong = $matches2[1];
				if (preg_match('/right_longitude=([\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->rgtlong = $matches2[1];

				$this->center=0;
				// zoom varies from 0 to 20 recommonded
				$this->zoom=1; 

				// get center and zoom level
				if (preg_match('/center=([\+\-]?[0-9\.]+,[\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->center = $matches2[1];
				if (preg_match('/zoom=([\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->zoom = $matches2[1];

				if($this->center){
					//seperate lon lat
					$this->centerlatlon=  explode(',', $this->center);

					// assign center lat , lon to special variables
					$this->centerlat=$this->centerlatlon[0];
					$this->centerlon=  $this->centerlatlon[1];


					// constants need for bounds calculation
					$this->LATITUDE_CONSTANT=90.0;
					$this->LONGITUDE_CONSTANT=250.0;
					$this->ZOOM_MULTIPLIER= 1.0/ pow(1.75, $this->zoom);

					// calculate latitude difference
					$this->latDiff=$this->LATITUDE_CONSTANT*$this->ZOOM_MULTIPLIER;

					// calculate longitude difference
					$this->lonDiff=$this->LONGITUDE_CONSTANT*$this->ZOOM_MULTIPLIER;

					// assign new values to boarder variables
					$this->btmlat =  $this->centerlat-$this->latDiff;
					$this->toplat =$this->centerlat+$this->latDiff;
					$this->lftlong =$this->centerlon-$this->lonDiff;
					$this->rgtlong=$this->centerlon+$this->lonDiff;

					// need special consideration about Latitude values
					if($this->btmlat<-89)$this->btmlat=-89.0;
					if($this->toplat>+89)$this->toplat=+89.0;

				}

				// get height and width
				$this->height =350;
				$this->width = 425;
				$this->layer='mapnik';

				if (preg_match('/height=([\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->height = $matches2[1];
				if (preg_match('/width=([\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->width = $matches2[1];
				if (preg_match('/layer=(mapnik|transportmap|cyclemap|mapquest)/', $match[1], $matches2)) $this->layer = $matches2[1];//offset matches2[1]

				// create string for iframe
				if($this->btmlat||$this->toplat||$this->lftlong||$this->rgtlong)
				{
					$this->str= 'width="'.$this->width .'" height="'.$this->height.'" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://www.openstreetmap.org/export/embed.html?bbox='.$this->lftlong.','.$this->btmlat.','.$this->rgtlong.','.$this->toplat.'&amp;layer='.$this->layer;
				}else{
	
					// print error message
				}

				$this->mrklat=0;
				$this->mrklong=0;

				// take markers - only one marker
				if (preg_match('/marker_latitude=([\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->mrklat = $matches2[1];
				if (preg_match('/marker_longitude=([\+\-]?[0-9\.]+)/', $match[1], $matches2)) $this->mrklong = $matches2[1];

				// append string if markers needed
				if($this->mrklat || $this->mrklong)
				{
					$this->str.='&amp;marker='.$this->mrklat.','.$this->mrklong;
				}
				else
				{
			
					// print error message
				}

				//adding iframe tags and border
				$this->str='<iframe '.$this->str.'" style="border: 1px solid black"></iframe>';

				// replace {openstreetmaps ...} with <iframe ...></iframe> tags
				$article->text = str_replace($match[0],$this->str,$article->text);
			}

		}


	}

}

?>
