<?php

/*
pCharts - class with charts

Version     : 0.4
Made by     : Forked by Momchil Bozhinov from the original pDraw class from Jean-Damien POGOLOTTI
Last Update : 01/02/2018

Contains functions:
	drawPolygonChart
	drawStackedAreaChart
	drawStackedBarChart
	drawBarChart
	drawAreaChart
	drawFilledStepChart
	drawStepChart
	drawZoneChart
	drawLineChart
	drawFilledSplineChart
	drawSplineChart
	drawPlotChart
	drawSplitPath
	drawDerivative
	drawBestFit

This file can be distributed under the license you can find at:
http://www.pchart.net/license

You can find the whole class documentation on the pChart web site.
*/

namespace pChart;

define("TEXT_POS_TOP", 690001);
define("TEXT_POS_RIGHT", 690002);

class pCharts {

	var $myPicture;

	/* Class creator */
	function __construct($pChartObject)
	{
		if (!($pChartObject instanceof pDraw)){
			die("Charts needs a pDraw object. Please check the examples.");
		}
		
		$this->myPicture = $pChartObject;
	}

	function getXstep($Orientation, $XDivs, $XMargin){

		if ($Orientation == SCALE_POS_LEFTRIGHT) {
			if ($XDivs == 0) {
				$XStep = $this->myPicture->GraphAreaXdiff / 4;
			} else {
				$XStep = ($this->myPicture->GraphAreaXdiff - $XMargin * 2) / $XDivs;
			}
		} else {
			if ($XDivs == 0) {
				$XStep = $this->myPicture->GraphAreaYdiff / 4;
			} else {
				$XStep = ($this->myPicture->GraphAreaYdiff - $XMargin * 2) / $XDivs;
			}
		}
		
		return $XStep;
	}

	/* Draw a plot chart */
	function drawPlotChart(array $Format = [])
	{
		$PlotSize = NULL;
		$PlotBorder = FALSE;
		$BorderColor = new pColor(50,50,50,30);
		$BorderSize = 2;
		$Surrounding = NULL;
		$DisplayValues = FALSE;
		$DisplayOffset = 4;
		$DisplayType = DISPLAY_MANUAL; # was display color
		$DisplayColor = new pColor(0);
		$RecordImageMap = FALSE;
		
		/* Override defaults */
		extract($Format);

		$this->myPicture->isChartLayoutStacked = FALSE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"]&& $SerieName != $Data["Abscissa"]) {
				$SerieWeight = (isset($Serie["Weight"])) ? $Serie["Weight"] + 2 : 2;
				(!is_null($PlotSize)) AND $SerieWeight = $PlotSize;
				(!is_null($Surrounding)) AND $BorderColor = $Serie["Color"]->newOne()->RGBChange($Surrounding);
				
				# Momchil: Force default Alpha as it is not set in the original code
				# That is for the example.Combo.area.lines
				$Color = $Serie["Color"]->newOne()->AlphaSet(100);
				
				if (!is_null($Serie["Picture"])) {
					$Picture = $Serie["Picture"];
					$PicInfo = $this->myPicture->getPicInfo($Picture);
					list($PicWidth, $PicHeight, $PicType) = $PicInfo;
				} else {
					$Picture = NULL;
					$PicOffset = 0;
				}

				if ($DisplayType == DISPLAY_AUTO) {
					$DisplayColor = $Color->newOne();
				}

				$AxisID = $Serie["Axis"];
				$Shape = $Serie["Shape"];
				$Mode = $Data["Axis"][$AxisID]["Display"];
				$Format = $Data["Axis"][$AxisID]["Format"];
				$Unit = $Data["Axis"][$AxisID]["Unit"];
				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = 0;
				
				if ($RecordImageMap) {
					$SerieDescription = (isset($Serie["Description"])) ? $Serie["Description"] : $SerieName;
					$ImageMapColor = $Color->toHTMLColor();
				}

				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);

				if (!is_null($Picture)) {
					$PicOffset = $PicWidth / 2;
					$SerieWeight = 0;
				}
				
				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {

					$X = $this->myPicture->GraphAreaX1 + $XMargin;

					foreach($PosArray as $Key => $Y) {
						if ($DisplayValues) {
							$this->myPicture->drawText(
								$X,
								$Y - $DisplayOffset - $SerieWeight - $BorderSize - $PicOffset,
								$this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit),
								["Color" => $DisplayColor,"Align" => TEXT_ALIGN_BOTTOMMIDDLE]
							);
						}
						if ($Y != VOID) {
							if ($RecordImageMap) {
								$this->myPicture->addToImageMap("CIRCLE", floor($X) . "," . floor($Y) . "," . $SerieWeight, $ImageMapColor, $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}

							if (!is_null($Picture)) {
								$this->myPicture->drawFromPicture($PicInfo, $Picture, $X - $PicWidth / 2, $Y - $PicHeight / 2);
							} else {
								$this->myPicture->drawShape($X, $Y, $Shape, $SerieWeight, $PlotBorder, $BorderSize, $Color, $BorderColor);
							}
						}

						$X += $XStep;
					}
					
				} else {

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
					
					foreach($PosArray as $Key => $X) {
						if ($DisplayValues) $this->myPicture->drawText(
							$X + $DisplayOffset + $SerieWeight + $BorderSize + $PicOffset,
							$Y,
							$this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit),
							["Angle" => 270,"Color" => $DisplayColor,"Align" => TEXT_ALIGN_BOTTOMMIDDLE]
						);
						if ($X != VOID) {
							if ($RecordImageMap) {
								$this->myPicture->addToImageMap("CIRCLE", floor($X) . "," . floor($Y) . "," . $SerieWeight, $ImageMapColor, $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}

							if (!is_null($Picture)) {
								$this->myPicture->drawFromPicture($PicInfo, $Picture, $X - $PicWidth / 2, $Y - $PicHeight / 2);
							} else {
								$this->myPicture->drawShape($X, $Y, $Shape, $SerieWeight, $PlotBorder, $BorderSize, $Color, $BorderColor);
							}
						}

						$Y += $XStep;
					}
				}
			}
		}
	}

	/* Draw a spline chart */
	function drawSplineChart(array $Format = [])
	{
		$BreakVoid = TRUE;
		$VoidTicks = 4;
		$BreakColor = NULL;
		$DisplayValues = FALSE;
		$DisplayOffset = 2;
		$DisplayType = DISPLAY_MANUAL;
		$DisplayColor = new pColor(0);
		$RecordImageMap = FALSE;
		$ImageMapPlotSize = 5;
		
		/* Override defaults */
		extract($Format);
		
		$this->myPicture->isChartLayoutStacked = FALSE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				$Ticks = $Serie["Ticks"];
				$Weight = $Serie["Weight"];
				if (is_null($BreakColor)) {
					$BreakSettings = ["Color" => $Serie["Color"], "Ticks" => $VoidTicks];
				} else {
					$BreakSettings = ["Color" => $BreakColor->AlphaSet($Serie["Color"]->Alpha), "Ticks" => $VoidTicks,"Weight" => $Weight];
				}

				if ($DisplayType == DISPLAY_AUTO) {
					$DisplayColor = $Serie["Color"];
				}

				$AxisID = $Serie["Axis"];
				$Mode = $Data["Axis"][$AxisID]["Display"];
				$Format = $Data["Axis"][$AxisID]["Format"];
				$Unit = $Data["Axis"][$AxisID]["Unit"];
				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = 0;
				
				$WayPoints = [];
				$LastGoodY = NULL;
				$LastGoodX = NULL;
				$LastX = 1;
				$LastY = 1;
				
				if ($RecordImageMap) {
					$SerieDescription = (isset($Serie["Description"])) ? $Serie["Description"] : $SerieName;
					$ImageMapColor = $Serie["Color"]->toHTMLColor();
				}

				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);

				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {

					$X = $this->myPicture->GraphAreaX1 + $XMargin;
					$Force = $XStep / 5;
	
					foreach($PosArray as $Key => $Y) {

						if ($DisplayValues) {
							$this->myPicture->drawText(
								$X,
								$Y - $DisplayOffset, 
								$this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), 
								["Color" => $DisplayColor, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]
							);
						}
						
						if ($Y != VOID){
							if ($RecordImageMap) {
								$this->myPicture->addToImageMap("CIRCLE", floor($X) . "," . floor($Y) . "," . $ImageMapPlotSize, $ImageMapColor, $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}
							
							if (($LastY == VOID) && !is_null($LastGoodY) && !$BreakVoid) {
								$this->myPicture->drawLine($LastGoodX, $LastGoodY, $X, $Y, $BreakSettings);
							}

							$WayPoints[] = [$X,$Y];
							$LastGoodY = $Y;
							$LastGoodX = $X;
							
						} else {
							if ($LastY != VOID) {
								$this->myPicture->drawSpline($WayPoints, ["Color" => $Serie["Color"], "Ticks" => $Ticks,"Weight" => $Weight]);
								$WayPoints = [];
							}
						}

						$LastY = $Y;
						$X += $XStep;
					}
					
					$this->myPicture->drawSpline($WayPoints, ["Force" => $Force, "Color" => $Serie["Color"], "Ticks" => $Ticks,"Weight" => $Weight]);
					
				} else {

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
					$Force = $XStep / 5;

					foreach($PosArray as $Key => $X) {

						if ($DisplayValues) {
							$this->myPicture->drawText(
								$X + $DisplayOffset,
								$Y, $this->myPicture->scaleFormat($Serie["Data"][$Key],$Mode, $Format, $Unit),
								["Angle" => 270,"Color" => $DisplayColor,"Align" => TEXT_ALIGN_BOTTOMMIDDLE]
							);
						}
				
						if ($X != VOID) {
							if ($RecordImageMap) {
								$this->myPicture->addToImageMap("CIRCLE", floor($X) . "," . floor($Y) . "," . $ImageMapPlotSize, $ImageMapColor, $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}
							
							if (($LastX == VOID) && !is_null($LastGoodX) && !$BreakVoid) {
								$this->myPicture->drawLine($LastGoodX, $LastGoodY, $X, $Y, $BreakSettings);
							}
							
							$WayPoints[] = [$X,	$Y];
							$LastGoodX = $X;
							$LastGoodY = $Y;
							
						} else {
							if ($LastX != VOID) {
								$this->myPicture->drawSpline($WayPoints, ["Force" => $Force,"Color" => $Serie["Color"],"Ticks" => $Ticks,"Weight" => $Weight]);
								$WayPoints = [];
							}
						}

						$LastX = $X;
						$Y += $XStep;
					}

					$this->myPicture->drawSpline($WayPoints, ["Force" => $Force,"Color" => $Serie["Color"],"Ticks" => $Ticks,"Weight" => $Weight]);
				}
			}
		}
	}

	/* Draw a filled spline chart */
	function drawFilledSplineChart(array $Format = [])
	{
		$DisplayValues = FALSE;
		$DisplayOffset = 2;
		$DisplayType = DISPLAY_MANUAL;
		$DisplayColor = new pColor(0);
		$AroundZero = TRUE;
		$Threshold = [];
		
		/* Override defaults */
		extract($Format);
		
		$this->myPicture->isChartLayoutStacked = FALSE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				$Color = $Serie["Color"];
				$ColorHalfAlfa = $Color->NewOne()->AlphaSlash(2);
				$Ticks = $Serie["Ticks"];
				if ($DisplayType == DISPLAY_AUTO) {
					$DisplayColor = $Color;
				}

				$AxisID = $Serie["Axis"];
				$Mode = $Data["Axis"][$AxisID]["Display"];
				$Format = $Data["Axis"][$AxisID]["Format"];
				$Unit = $Data["Axis"][$AxisID]["Unit"];
				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				if ($AroundZero) {
					$YZero = $this->myPicture->scaleComputeYSingle(0, $AxisID);
				}

				foreach($Threshold as $Key => $Params) {
					$Threshold[$Key]["MinX"] = $this->myPicture->scaleComputeYSingle($Params["Min"], $AxisID);
					$Threshold[$Key]["MaxX"] = $this->myPicture->scaleComputeYSingle($Params["Max"], $AxisID);
				}

				$this->myData->Data["Series"][$SerieName]["XOffset"] = 0;
				
				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);
				
				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {

					$X = $this->myPicture->GraphAreaX1 + $XMargin;
					$WayPoints = [];
					$Force = $XStep / 5;
					if (!$AroundZero) {
						$YZero = $this->myPicture->GraphAreaY2 - 1;
					}

					if ($YZero > $this->myPicture->GraphAreaY2 - 1) {
						$YZero = $this->myPicture->GraphAreaY2 - 1;
					}

					if ($YZero < $this->myPicture->GraphAreaY1 + 1) {
						$YZero = $this->myPicture->GraphAreaY1 + 1;
					}
				
					foreach($PosArray as $Key => $Y) {
						if ($DisplayValues) {
							$this->myPicture->drawText($X, $Y - $DisplayOffset, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), ["Color" => $DisplayColor,"Align" => TEXT_ALIGN_BOTTOMMIDDLE]);
						}

						if ($Y == VOID) {
							$Area = $this->myPicture->drawSpline($WayPoints, ["Force" => $Force,"NoDraw" => TRUE]);

							foreach($Area as $Points) {
								$Corners = [$Points[0]["X"], $YZero];
								$PointCount = count($Points) - 1;
								foreach($Points as $sKey => $Point) {
									$Corners[] = ($sKey == $PointCount) ? $Point["X"] - 1 : $Point["X"];
									$Corners[] = $Point["Y"] + 1;
								}

								$Corners[] = $Points[$sKey]["X"] - 1;
								$Corners[] = $YZero;

								$this->drawPolygonChart($Corners, ["Color" => $ColorHalfAlfa,"NoBorder" => TRUE,"Threshold" => $Threshold]);
							}

							$this->myPicture->drawSpline($WayPoints, ["Force" => $Force,"Color" => $Color,"Ticks" => $Ticks]);

							$WayPoints = [];
						} else {
							$WayPoints[] = [$X,$Y - .5]; /* -.5 for AA visual fix */
						}

						$X += $XStep;
					}

					$Area = $this->myPicture->drawSpline($WayPoints, ["Force" => $Force,"NoDraw" => TRUE]);

					foreach($Area as $Points) {
						$Corners = [$Points[0]["X"], $YZero];
						$PointCount = count($Points) - 1;
						foreach($Points as $sKey => $Point) {
							$Corners[] = ($sKey == $PointCount) ? $Point["X"] - 1 : $Point["X"];
							$Corners[] = $Point["Y"] + 1;
						}
						
						$Corners[] = $Points[$sKey]["X"] - 1;
						$Corners[] = $YZero;
						
						$this->drawPolygonChart($Corners, ["Color" => $ColorHalfAlfa,"NoBorder" => TRUE,"Threshold" => $Threshold]);
					}

					$this->myPicture->drawSpline($WayPoints, ["Force" => $Force,"Color" => $Color,"Ticks" => $Ticks]);

				} else {

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
					$WayPoints = [];
					$Force = $XStep / 5;
					if (!$AroundZero) {
						$YZero = $this->myPicture->GraphAreaX1 + 1;
					}

					if ($YZero > $this->myPicture->GraphAreaX2 - 1) {
						$YZero = $this->myPicture->GraphAreaX2 - 1;
					}

					if ($YZero < $this->myPicture->GraphAreaX1 + 1) {
						$YZero = $this->myPicture->GraphAreaX1 + 1;
					}
					
					foreach($PosArray as $Key => $X) {
						if ($DisplayValues) {
							$this->myPicture->drawText(
								$X + $DisplayOffset,
								$Y,
								$this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit),
								["Angle" => 270,"Color" => $DisplayColor, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]
							);
						}

						if ($X == VOID) {
							$Area = $this->myPicture->drawSpline($WayPoints, ["Force" => $Force,"NoDraw" => TRUE]);

							foreach($Area as $Points) {
								$Corners = [$YZero, $Points[0]["Y"]];
								$PointCount = count($Points) - 1;
								foreach($Points as $sKey => $Point) {
									$Corners[] = ($sKey == $PointCount) ? $Point["X"] - 1 : $Point["X"];
									$Corners[] = $Point["Y"];
								}

								$Corners[] = $YZero;
								$Corners[] = $Points[$sKey]["Y"] - 1;
								$this->drawPolygonChart($Corners, ["Color" => $ColorHalfAlfa,"NoBorder" => TRUE,"Threshold" => $Threshold]);
							}

							$this->myPicture->drawSpline($WayPoints, ["Color" => $ColorHalfAlfa,"NoBorder" => TRUE,"Threshold" => $Threshold]);

							$WayPoints = [];
						} else {
							$WayPoints[] = [$X,$Y];
						}

						$Y += $XStep;
					}

					$Area = $this->myPicture->drawSpline($WayPoints, ["Force" => $Force,"NoDraw" => TRUE]);

					foreach($Area as $Points) {
						$Corners = [$YZero, $Points[0]["Y"]];
						$PointCount = count($Points) - 1;
						foreach($Points as $sKey => $Point) {
							$Corners[] = ($sKey == $PointCount) ? $Point["X"] - 1 : $Point["X"];
							$Corners[] = $Point["Y"];
						}

						$Corners[] = $YZero;
						$Corners[] = $Points[$sKey]["Y"] - 1;
						$this->drawPolygonChart($Corners, ["Force" => $Force,"Color" => $Color,"Ticks" => $Ticks]);
					}

					$this->myPicture->drawSpline($WayPoints, ["Force" => $Force,"Color" => $Color,"Ticks" => $Ticks]);
				}
			}
		}
	}

	/* Draw a line chart */
	function drawLineChart(array $Format = [])
	{
		$BreakVoid = TRUE;
		$VoidTicks = 4;
		$BreakColor = NULL;
		$DisplayValues = FALSE;
		$DisplayOffset = 2;
		$DisplayType = DISPLAY_MANUAL;
		$DisplayColor = new pColor(0);
		$RecordImageMap = FALSE;
		$ImageMapPlotSize = 5;
		$UseForcedColor = FALSE;
		$ForceColor = new pColor(0,0,0,100);
		
		/* Override defaults */
		extract($Format);
		
		$this->myPicture->isChartLayoutStacked = FALSE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				$Color = $Serie["Color"]->newOne();
				$Ticks = $Serie["Ticks"];
				$Weight = $Serie["Weight"];
				if ($UseForcedColor) {
					$Color = $ForceColor;
				}

				if (is_null($BreakColor)) {
					$BreakSettings = ["Color" => $Color,"Ticks" => $VoidTicks,"Weight" => $Weight];
				} else {
					$BreakSettings = ["Color" => $BreakColor,"Ticks" => $VoidTicks,"Weight" => $Weight];
				}

				if ($DisplayType == DISPLAY_AUTO) {
					$DisplayColor = $Color;
				}
				
				# Momchil: Force default Alpha as it is not set in the original code
				# That is for the example.Combo.area.lines
				$BreakSettings["Color"]->AlphaSet(100);

				$AxisID = $Serie["Axis"];
				$Mode = $Data["Axis"][$AxisID]["Display"];
				$Format = $Data["Axis"][$AxisID]["Format"];
				$Unit = $Data["Axis"][$AxisID]["Unit"];
				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = 0;
				
				$LastX = VOID;
				$LastY = VOID;
				$LastGoodY = NULL;
				$LastGoodX = NULL;
				
				if ($RecordImageMap) {
					$SerieDescription = (isset($Serie["Description"])) ? $Serie["Description"] : $SerieName;
					$ImageMapColor = $Color->toHTMLColor();
				}
				
				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);
				
				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {

					$X = $this->myPicture->GraphAreaX1 + $XMargin;

					foreach($PosArray as $Key => $Y) {
						
						if ($DisplayValues && $Serie["Data"][$Key] != VOID) {
							if ($Serie["Data"][$Key] > 0) {
								$Align = TEXT_ALIGN_BOTTOMMIDDLE;
								$Offset = $DisplayOffset;
							} else {
								$Align = TEXT_ALIGN_TOPMIDDLE;
								$Offset = - $DisplayOffset;
							}

							$this->myPicture->drawText(
								$X,
								$Y - $Offset - $Weight,
								$this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit),
								["Color" => $DisplayColor,"Align" => $Align]
							);
						}

						if ($Y != VOID){
							
							if ($RecordImageMap) {
								$this->myPicture->addToImageMap("CIRCLE", floor($X) . "," . floor($Y) . "," . $ImageMapPlotSize, $ImageMapColor, $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}

							if (($LastX != VOID) && ($LastY != VOID)){
								$this->myPicture->drawLine($LastX, $LastY, $X, $Y, ["Color" => $Color,"Ticks" => $Ticks,"Weight" => $Weight]);
							}
							
							if (($LastY == VOID) && !is_null($LastGoodY) && !$BreakVoid) {
								$this->myPicture->drawLine($LastGoodX, $LastGoodY, $X, $Y, $BreakSettings);
								$LastGoodY = NULL;
							}

							$LastGoodY = $Y;
							$LastGoodX = $X;
						}

						$LastX = $X;
						$LastY = $Y;
						$X += $XStep;
					}
					
				} else {

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
					
					foreach($PosArray as $Key => $X) {
						
						if ($DisplayValues && $Serie["Data"][$Key] != VOID) {
							$this->myPicture->drawText(
								$X + $DisplayOffset + $Weight,
								$Y,
								$this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit),
								["Angle" => 270,"Color" => $DisplayColor,"Align" => TEXT_ALIGN_BOTTOMMIDDLE]
							);
						}

						if ($X != VOID){
							if ($RecordImageMap){
								$this->myPicture->addToImageMap("CIRCLE", floor($X) . "," . floor($Y) . "," . $ImageMapPlotSize, $ImageMapColor, $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}

							if (($LastX != VOID) && !is_null($LastY)){
								$this->myPicture->drawLine($LastX, $LastY, $X, $Y, ["Color" => $Color,"Ticks" => $Ticks,"Weight" => $Weight]);
							}
							
							if (($LastX == VOID) && !is_null($LastGoodY) && !$BreakVoid) {
								$this->myPicture->drawLine($LastGoodX, $LastGoodY, $X, $Y, $BreakSettings);
								$LastGoodY = NULL;
							}
							
							$LastGoodY = $Y;
							$LastGoodX = $X;
						}

						$LastX = $X;
						$LastY = $Y;
						$Y += $XStep;
					}

				}
			}
		}
	}

	/* Draw a line chart */
	function drawZoneChart(string $SerieA, string $SerieB, array $Format = [])
	{
		$AxisID = isset($Format["AxisID"]) ? $Format["AxisID"] : 0;
		$LineColor = isset($Format["LineColor"]) ? $Format["LineColor"] : new pColor(150,150,150,50);
		$LineTicks = isset($Format["LineTicks"]) ? $Format["LineTicks"] : 1;
		$AreaColor = isset($Format["AreaColor"]) ? $Format["AreaColor"] : new pColor(150,150,150,5);

		$this->myPicture->isChartLayoutStacked = FALSE;
		$Data = $this->myPicture->myData->Data;
		if (!isset($Data["Series"][$SerieA]["Data"]) || !isset($Data["Series"][$SerieB]["Data"])) {
			throw pException::ZoneChartInvalidInputException("Invalid data #1");
		}

		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		$PosArrayA = $this->myPicture->scaleComputeY($Data["Series"][$SerieA]["Data"], $AxisID);
		$PosArrayB = $this->myPicture->scaleComputeY($Data["Series"][$SerieB]["Data"], $AxisID);
		if (count($PosArrayA) != count($PosArrayB)) {
			throw pException::ZoneChartInvalidInputException("Invalid data #2");
		}
	
		$BoundsA = [];
		$BoundsB = [];

		$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);

		if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {

			$X = $this->myPicture->GraphAreaX1 + $XMargin;

			foreach($PosArrayA as $Key => $Y1) {
				$BoundsA[] = $X;
				$BoundsA[] = $Y1;
				$BoundsB[] = $X;
				$BoundsB[] = $PosArrayB[$Key]; #Y2
				$X += $XStep;
			}

			$Bounds = array_merge($BoundsA, $this->myPicture->reversePlots($BoundsB));
			$this->drawPolygonChart($Bounds, ["Color" => $AreaColor]);
			for ($i = 0; $i <= count($BoundsA) - 4; $i = $i + 2) {
				$this->myPicture->drawLine($BoundsA[$i], $BoundsA[$i + 1], $BoundsA[$i + 2], $BoundsA[$i + 3], ["Color" => $LineColor,"Ticks" => $LineTicks]);
				$this->myPicture->drawLine($BoundsB[$i], $BoundsB[$i + 1], $BoundsB[$i + 2], $BoundsB[$i + 3], ["Color" => $LineColor,"Ticks" => $LineTicks]);
			}
		} else {

			$Y = $this->myPicture->GraphAreaY1 + $XMargin;

			foreach($PosArrayA as $Key => $X1) {
				$BoundsA[] = $X1;
				$BoundsA[] = $Y;
				$BoundsB[] = $PosArrayB[$Key];#X2
				$BoundsB[] = $Y;
				$Y += $XStep;
			}

			$Bounds = array_merge($BoundsA, $this->myPicture->reversePlots($BoundsB));
			$this->drawPolygonChart($Bounds, ["Color" => $AreaColor]);
			for ($i = 0; $i <= count($BoundsA) - 4; $i = $i + 2) {
				$this->myPicture->drawLine($BoundsA[$i], $BoundsA[$i + 1], $BoundsA[$i + 2], $BoundsA[$i + 3], ["Color" => $LineColor,"Ticks" => $LineTicks]);
				$this->myPicture->drawLine($BoundsB[$i], $BoundsB[$i + 1], $BoundsB[$i + 2], $BoundsB[$i + 3], ["Color" => $LineColor,"Ticks" => $LineTicks]);
			}
		}
	}

	/* Draw a step chart */
	function drawStepChart(array $Format = [])
	{
		$BreakVoid = FALSE;
		$ReCenter = TRUE;
		$VoidTicks = 4;
		$BreakColor = NULL; # Same Alpha as $Color
		$DisplayValues = FALSE;
		$DisplayOffset = 2;
		$DisplayType = DISPLAY_MANUAL;
		$DisplayColor = new pColor(0);
		$RecordImageMap = FALSE;
		$ImageMapPlotSize = 5;
		
		/* Override defaults */
		extract($Format);
		
		$this->myPicture->isChartLayoutStacked = FALSE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				$Color = $Serie["Color"]->newOne();
				$Ticks = $Serie["Ticks"];
				$Weight = $Serie["Weight"];

				if (is_null($BreakColor)) {
					$BreakSettings = ["Color" => $Color,"Ticks" => $VoidTicks,"Weight" => $Weight];
				} else {
					$BreakSettings = ["Color" => $BreakColor,"Ticks" => $VoidTicks,"Weight" => $Weight];
				}

				if ($DisplayType == DISPLAY_AUTO) {
					$DisplayColor = $Color;
				}

				$AxisID = $Serie["Axis"];
				$Mode = $Data["Axis"][$AxisID]["Display"];
				$Format = $Data["Axis"][$AxisID]["Format"];
				$Unit = $Data["Axis"][$AxisID]["Unit"];
				$LineSettings = ["Color" => $Color,"Ticks" => $Ticks,"Weight" => $Weight];
				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = 0;
				
				$LastX = VOID;
				$LastY = VOID;
				$LastGoodY = NULL;
				$LastGoodX = NULL;
				$Init = FALSE;
				
				if ($RecordImageMap) {
					$SerieDescription = (isset($Serie["Description"])) ? $Serie["Description"] : $SerieName;
					$ImageMapColor = $Color->toHTMLColor();
				}
				
				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);
				
				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {

					$X = $this->myPicture->GraphAreaX1 + $XMargin;

					foreach($PosArray as $Key => $Y) {
						if ($DisplayValues && $Serie["Data"][$Key] != VOID) {
							if ($Y <= $LastY) {
								$Align = TEXT_ALIGN_BOTTOMMIDDLE;
								$Offset = $DisplayOffset;
							} else {
								$Align = TEXT_ALIGN_TOPMIDDLE;
								$Offset = - $DisplayOffset;
							}

							$this->myPicture->drawText($X, $Y - $Offset - $Weight, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), ["Color" => $DisplayColor,"Align" => $Align]);
						}

						if ($Y != VOID){
							
							if (($LastX != VOID) && ($LastY != VOID)) {
								$this->myPicture->drawLine($LastX, $LastY, $X, $LastY, $LineSettings);
								$this->myPicture->drawLine($X, $LastY, $X, $Y, $LineSettings);
								if ($ReCenter && $X + $XStep < $this->myPicture->GraphAreaX2 - $XMargin) {
									$this->myPicture->drawLine($X, $Y, $X + $XStep, $Y, $LineSettings);
									$RecordImageMapStr = floor($X - $ImageMapPlotSize) . "," . floor($Y - $ImageMapPlotSize) . "," . floor($X + $XStep + $ImageMapPlotSize) . "," . floor($Y + $ImageMapPlotSize);
								} else {
									$RecordImageMapStr = floor($LastX - $ImageMapPlotSize) . "," . floor($LastY - $ImageMapPlotSize) . "," . floor($X + $ImageMapPlotSize) . "," . floor($LastY + $ImageMapPlotSize);
								}
							}
							
							if (($LastY == VOID) && !is_null($LastGoodY) && !$BreakVoid) {
								
								$LastGoodXPlusStep = ($ReCenter) ? $LastGoodX + $XStep : $LastGoodX;
								$RecordImageMapStr = floor($LastGoodXPlusStep - $ImageMapPlotSize) . "," . floor($LastGoodY - $ImageMapPlotSize) . "," . floor($X + $ImageMapPlotSize) . "," . floor($LastGoodY + $ImageMapPlotSize);
								
								$this->myPicture->drawLine($LastGoodXPlusStep, $LastGoodY, $X, $LastGoodY, $BreakSettings);
								$this->myPicture->drawLine($X, $LastGoodY, $X, $Y, $BreakSettings);
								$LastGoodY = NULL;
								
							} elseif (!$BreakVoid && is_null($LastGoodY)) {
								$this->myPicture->drawLine($this->myPicture->GraphAreaX1 + $XMargin, $Y, $X, $Y, $BreakSettings);
								$RecordImageMapStr = floor($this->myPicture->GraphAreaX1 + $XMargin - $ImageMapPlotSize) . "," . floor($Y - $ImageMapPlotSize) . "," . floor($X + $ImageMapPlotSize) . "," . floor($Y + $ImageMapPlotSize);
							}

							if ($RecordImageMap) {
								$this->myPicture->addToImageMap("RECT", $RecordImageMapStr, $ImageMapColor, $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}

							$LastGoodY = $Y;
							$LastGoodX = $X;
						}

						if (!$Init && $ReCenter) {
							$X = $X - $XStep / 2;
							$Init = TRUE;
						}

						$LastX = $X;
						$LastY = $Y;
						if ($LastX < $this->myPicture->GraphAreaX1 + $XMargin) {
							$LastX = $this->myPicture->GraphAreaX1 + $XMargin;
						}

						$X += $XStep;
					}

					if ($ReCenter) {
						$this->myPicture->drawLine($LastX, $LastY, $this->myPicture->GraphAreaX2 - $XMargin, $LastY, $LineSettings);
						if ($RecordImageMap) {
							$this->myPicture->addToImageMap("RECT", floor($LastX - $ImageMapPlotSize) . "," . floor($LastY - $ImageMapPlotSize) . "," . floor($this->myPicture->GraphAreaX2 - $XMargin + $ImageMapPlotSize) . "," . floor($LastY + $ImageMapPlotSize), $Color->toHTMLColor(), $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
						}
					}

				} else {

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
	
					foreach($PosArray as $Key => $X) {
						if ($DisplayValues && $Serie["Data"][$Key] != VOID) {
							if ($X >= $LastX) {
								$Align = TEXT_ALIGN_MIDDLELEFT;
								$Offset = $DisplayOffset;
							} else {
								$Align = TEXT_ALIGN_MIDDLERIGHT;
								$Offset = - $DisplayOffset;
							}

							$this->myPicture->drawText($X + $Offset + $Weight, $Y, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), ["Color" => $DisplayColor,"Align" => $Align]);
						}

						if ($X != VOID){
							if (($LastX != VOID) && ($LastY != VOID)) {
								$this->myPicture->drawLine($LastX, $LastY, $LastX, $Y, $LineSettings);
								$this->myPicture->drawLine($LastX, $Y, $X, $Y, $LineSettings);
								$RecordImageMapStr = floor($LastX - $ImageMapPlotSize) . "," . floor($LastY - $ImageMapPlotSize) . "," . floor($LastX + $XStep + $ImageMapPlotSize) . "," . floor($Y + $ImageMapPlotSize);
							}

							if (($LastX == VOID) && !is_null($LastGoodY) && !$BreakVoid) {
								$this->myPicture->drawLine($LastGoodX, $LastGoodY, $LastGoodX, $LastGoodY + $XStep, $LineSettings);
								$RecordImageMapStr = floor($LastGoodX - $ImageMapPlotSize) . "," . floor($LastGoodY - $ImageMapPlotSize) . "," . floor($LastGoodX + $ImageMapPlotSize) . "," . floor($LastGoodY + $XStep + $ImageMapPlotSize);

								$this->myPicture->drawLine($LastGoodX, $LastGoodY + $XStep, $LastGoodX, $Y, $BreakSettings);
								$RecordImageMapStr = floor($LastGoodX - $ImageMapPlotSize) . "," . floor($LastGoodY + $XStep - $ImageMapPlotSize) . "," . floor($LastGoodX + $ImageMapPlotSize) . "," . floor($XStep + $ImageMapPlotSize);

								$this->myPicture->drawLine($LastGoodX, $Y, $X, $Y, $BreakSettings);
								$LastGoodY = NULL;
							} elseif (is_null($LastGoodY) && !$BreakVoid) {
								$this->myPicture->drawLine($X, $this->myPicture->GraphAreaY1 + $XMargin, $X, $Y, $BreakSettings);
							}
							
							if ($RecordImageMap) {
								$this->myPicture->addToImageMap("RECT", $RecordImageMapStr, $Color->toHTMLColor(), $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}

							$LastGoodY = $Y;
							$LastGoodX = $X;
						}

						if (!$Init && $ReCenter) {
							$Y = $Y - $XStep / 2;
							$Init = TRUE;
						}

						$LastX = $X;
						$LastY = $Y;
						if ($LastY < $this->myPicture->GraphAreaY1 + $XMargin) {
							$LastY = $this->myPicture->GraphAreaY1 + $XMargin;
						}

						$Y += $XStep;
					}

					if ($ReCenter) {
						$this->myPicture->drawLine($LastX, $LastY, $LastX, $this->myPicture->GraphAreaY2 - $XMargin, $LineSettings);
						if ($RecordImageMap) {
							$this->myPicture->addToImageMap("RECT", floor($LastX - $ImageMapPlotSize) . "," . floor($LastY - $ImageMapPlotSize) . "," . floor($LastX + $ImageMapPlotSize) . "," . floor($this->myPicture->GraphAreaY2 - $XMargin + $ImageMapPlotSize), $Color->toHTMLColor(), $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
						}
					}
				}
			}
		}
	}

	/* Draw a step chart */
	function drawFilledStepChart(array $Format = [])
	{
		$ReCenter = TRUE;
		$DisplayValues = FALSE;
		$DisplayOffset = 2;
		$DisplayType = DISPLAY_MANUAL;
		$ForceTransparency = NULL;
		$DisplayColor = new pColor(0);
		$AroundZero = TRUE;
		
		/* Override defaults */
		extract($Format);
		
		$this->myPicture->isChartLayoutStacked = FALSE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				$Color = $Serie["Color"]->newOne();
				$Ticks = $Serie["Ticks"];
				$Weight = $Serie["Weight"];
				if ($DisplayType == DISPLAY_AUTO) {
					$DisplayColor = $Color;
				}

				$AxisID = $Serie["Axis"];
				$Mode = $Data["Axis"][$AxisID]["Display"];
				$Format = $Data["Axis"][$AxisID]["Format"];
				$Unit = $Data["Axis"][$AxisID]["Unit"];
				if (!is_null($ForceTransparency)) {
					$Color->AlphaSet($ForceTransparency);
				}
				$PolygonSettings = ["Color" => $Color];
				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				$YZero = $this->myPicture->scaleComputeYSingle(0, $Serie["Axis"]);
				$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = 0;
				
				$LastX = VOID;
				$LastY = VOID;
				$LastGoodY = NULL;
				$LastGoodX = NULL;
				$Points = [];
				
				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);

				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {
					if ($YZero > $this->myPicture->GraphAreaY2 - 1) {
						$YZero = $this->myPicture->GraphAreaY2 - 1;
					}

					if ($YZero < $this->myPicture->GraphAreaY1 + 1) {
						$YZero = $this->myPicture->GraphAreaY1 + 1;
					}

					$X = $this->myPicture->GraphAreaX1 + $XMargin;

					if (!$AroundZero) {
						$YZero = $this->myPicture->GraphAreaY2 - 1;
					}
					$Init = FALSE;

					foreach($PosArray as $Y) {

						if ($Y == VOID && ($LastX != VOID) && ($LastY != VOID) && (!empty($Points))) {
							$Points[] = $LastX;
							$Points[] = $LastY;
							$Points[] = $X;
							$Points[] = $LastY;
							$Points[] = $X;
							$Points[] = $YZero;
							$this->myPicture->drawPolygon($Points, $PolygonSettings);
							$Points = [];
						}

						if ($Y != VOID && ($LastX != VOID) && $LastY != VOID) {
							(empty($Points)) AND $Points = [$LastX, $YZero];
							$Points[] = $LastX;
							$Points[] = $LastY;
							$Points[] = $X;
							$Points[] = $LastY;
							$Points[] = $X;
							$Points[] = $Y;
						}

						if ($Y != VOID) {
							$LastGoodY = $Y;
							$LastGoodX = $X;
						} 
						
						if (!$Init && $ReCenter) {
							$X = $X - $XStep / 2;
							$Init = TRUE;
						}

						$LastX = $X;
						$LastY = $Y;
						if ($LastX < $this->myPicture->GraphAreaX1 + $XMargin) {
							$LastX = $this->myPicture->GraphAreaX1 + $XMargin;
						}

						$X += $XStep;
					}

					if ($ReCenter) {
						$Points[] = $LastX+$XStep/2; $Points[] = $LastY;
						$Points[] = $LastX+$XStep/2; $Points[] = $YZero;
					} else {
						$Points[] = $LastX;
						$Points[] = $YZero;
					}

					$this->myPicture->drawPolygon($Points, $PolygonSettings);

				} else {
					if ($YZero < $this->myPicture->GraphAreaX1 + 1) {
						$YZero = $this->myPicture->GraphAreaX1 + 1;
					}

					if ($YZero > $this->myPicture->GraphAreaX2 - 1) {
						$YZero = $this->myPicture->GraphAreaX2 - 1;
					}

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;

					foreach($PosArray as $X) {

						if ($X == VOID && ($LastX != VOID) && ($LastY != VOID) && (!empty($Points))) {
							$Points[] = $LastX;
							$Points[] = $LastY;
							$Points[] = $LastX;
							$Points[] = $Y;
							$Points[] = $YZero;
							$Points[] = $Y;
							$this->myPicture->drawPolygon($Points, $PolygonSettings);
							$Points = [];
						}

						if ($X != VOID && ($LastX != VOID) && ($LastY != VOID)) {
							(empty($Points)) AND $Points = [$YZero, $LastY];
							$Points[] = $LastX; 
							$Points[] = $LastY;
							$Points[] = $LastX;
							$Points[] = $Y;
							$Points[] = $X;
							$Points[] = $Y;
						}

						if ($X != VOID) {
							$LastGoodY = $Y;
							$LastGoodX = $X;
						}

						if (($LastX == VOID) && $ReCenter) {
							$Y = $Y - $XStep / 2;
						}

						$LastX = $X;
						$LastY = $Y;
						if ($LastY < $this->myPicture->GraphAreaY1 + $XMargin) {
							$LastY = $this->myPicture->GraphAreaY1 + $XMargin;
						}

						$Y += $XStep;
					}

					if ($ReCenter) {
						$Points[] = $LastX;
						$Points[] = $LastY+$XStep/2;
						$Points[] = $YZero;
						$Points[] = $LastY+$XStep/2;
					} else {
						$Points[] = $YZero;
						$Points[] = $LastY;
					}

					$this->myPicture->drawPolygon($Points, $PolygonSettings);
				}
			}
		}
	}

	/* Draw an area chart */
	function drawAreaChart(array $Format = [])
	{
		$DisplayValues = FALSE;
		$DisplayOffset = 2;
		$DisplayType = DISPLAY_MANUAL;
		$DisplayColor = new pColor(0);
		$ForceTransparency = 25;
		$AroundZero = TRUE;
		$Threshold = [];
		
		/* Override defaults */
		extract($Format);
		
		$this->myPicture->isChartLayoutStacked = FALSE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				$Color = $Serie["Color"]->newOne();
				if ($DisplayType == DISPLAY_AUTO) {
					$DisplayColor = $Color;
				}

				$AxisID = $Serie["Axis"];
				$Mode = $Data["Axis"][$AxisID]["Display"];
				$Format = $Data["Axis"][$AxisID]["Format"];
				$Unit = $Data["Axis"][$AxisID]["Unit"];
				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				$YZero = $this->myPicture->scaleComputeYSingle(0, $Serie["Axis"]);
				foreach($Threshold as $Key => $Params) {
					$Threshold[$Key]["MinX"] = $this->myPicture->scaleComputeYSingle($Params["Min"], $Serie["Axis"]);
					$Threshold[$Key]["MaxX"] = $this->myPicture->scaleComputeYSingle($Params["Max"], $Serie["Axis"]);
				}

				$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = 0;
				
				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);
				
				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {
					if ($YZero > $this->myPicture->GraphAreaY2 - 1) {
						$YZero = $this->myPicture->GraphAreaY2 - 1;
					}

					$AreaID = 0;
					$Areas = [$AreaID => [
						$this->myPicture->GraphAreaX1 + $XMargin,
						($AroundZero) ? $YZero : $this->myPicture->GraphAreaY2 - 1
					]];
	
					$X = $this->myPicture->GraphAreaX1 + $XMargin;
					$LastX = NULL;
					$LastY = NULL;
					
					foreach($PosArray as $Key => $Y) {
						if ($DisplayValues && $Serie["Data"][$Key] != VOID) {
							if ($Serie["Data"][$Key] > 0) {
								$Align = TEXT_ALIGN_BOTTOMMIDDLE;
								$Offset = $DisplayOffset;
							} else {
								$Align = TEXT_ALIGN_TOPMIDDLE;
								$Offset = - $DisplayOffset;
							}

							$this->myPicture->drawText($X, $Y - $Offset, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), ["Color" => $DisplayColor,"Align" => $Align]);
						}

						if ($Y == VOID && isset($Areas[$AreaID])) {
							$Areas[$AreaID][] = (is_null($LastX)) ? $X : $LastX;
							$Areas[$AreaID][] = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaY2 - 1;
							$AreaID++; # Momchil: Never gets here
						} elseif ($Y != VOID) {
							if (!isset($Areas[$AreaID])) {
								$Areas[$AreaID][] = $X;
								$Areas[$AreaID][] = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaY2 - 1;
							}

							$Areas[$AreaID][] = $X;
							$Areas[$AreaID][] = $Y;
						}

						$LastX = $X;
						$X = $X + $XStep;
					}

					$Areas[$AreaID][] = $LastX;
					$Areas[$AreaID][] = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaY2 - 1;

				} else {
					if ($YZero < $this->myPicture->GraphAreaX1 + 1) {
						$YZero = $this->myPicture->GraphAreaX1 + 1;
					}

					if ($YZero > $this->myPicture->GraphAreaX2 - 1) {
						$YZero = $this->myPicture->GraphAreaX2 - 1;
					}

					$AreaID = 0;
					$Areas = [$AreaID => [
						($AroundZero) ? $YZero : $this->myPicture->GraphAreaX1 + 1,
						$this->myPicture->GraphAreaY1 + $XMargin
					]];

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
					$LastX = NULL;
					$LastY = NULL;

					foreach($PosArray as $Key => $X) {
						if ($DisplayValues && $Serie["Data"][$Key] != VOID) {
							if ($Serie["Data"][$Key] > 0) {
								$Align = TEXT_ALIGN_BOTTOMMIDDLE;
								$Offset = $DisplayOffset;
							} else {
								$Align = TEXT_ALIGN_TOPMIDDLE;
								$Offset = - $DisplayOffset;
							}

							$this->myPicture->drawText($X + $Offset, $Y, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit),["Angle" => 270,"Color" => $DisplayColor,"Align" => $Align]);
						}

						if ($X == VOID && isset($Areas[$AreaID])) {
							$Areas[$AreaID][] = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaX1 + 1;
							$Areas[$AreaID][] = (is_null($LastY)) ? $Y : $LastY;
							$AreaID++;
						} elseif ($X != VOID) {
							if (!isset($Areas[$AreaID])) {
								$Areas[$AreaID][] = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaX1 + 1;
								$Areas[$AreaID][] = $Y;
							}

							$Areas[$AreaID][] = $X;
							$Areas[$AreaID][] = $Y;
						}

						$LastX = $X;
						$LastY = $Y;
						$Y = $Y + $XStep;
					}

					$Areas[$AreaID][] = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaX1 + 1;
					$Areas[$AreaID][] = $LastY;
				}
				
				/* Handle shadows in the areas */
				if ($this->myPicture->Shadow) {
					$ShadowArea = [];
					foreach($Areas as $Key => $Points) {
						$ShadowArea[$Key] = [];
						foreach($Points as $Key2 => $Value) {
							$ShadowArea[$Key][] = ($Key2 % 2 == 0) ? ($Value + $this->myPicture->ShadowX) : ($Value + $this->myPicture->ShadowY);
						}
					}

					foreach($ShadowArea as $Points) {
						$this->drawPolygonChart($Points, ["Color" => $this->myPicture->ShadowColor]);
					}
				}

				(!is_null($ForceTransparency)) AND $Color->AlphaSet($ForceTransparency);
				foreach($Areas as $Points) {
					$this->drawPolygonChart($Points, ["Color" => $Color,"Threshold" => $Threshold]);
				}
				
			}
		}
	}
	
	/* Draw a bar chart */
	function drawBarChart(array $Format = [])
	{
		$Floating0Serie = NULL;
		$Floating0Value = NULL;
		$Draw0Line = FALSE;
		$DisplayValues = FALSE;
		$DisplayOrientation = ORIENTATION_HORIZONTAL;
		$DisplayOffset = 2;
		$DisplayType = DISPLAY_MANUAL;
		$DisplayFont = $this->myPicture->FontName; 
		$DisplaySize = $this->myPicture->FontSize;
		$DisplayPos = LABEL_POS_OUTSIDE;
		$DisplayShadow = TRUE;
		$DisplayColor = NULL;
		$AroundZero = TRUE;
		$Interleave = .5;
		$Rounded = FALSE;
		$RoundRadius = 4;
		$Surrounding = NULL;
		$BorderColor = NULL;
		$Gradient = FALSE;
		$GradientMode = GRADIENT_SIMPLE;
		$GradientStartColor = new pColor(255,255,255,20);
		$GradientEndColor = new pColor(0,0,0,20);
		$TxtMargin = 6;
		$OverrideColors = [];
		$OverrideSurrounding = 30;
		$InnerSurrounding = NULL;
		$InnerBorderColor = NULL;
		$RecordImageMap = FALSE;
		
		/* Override defaults */
		extract($Format);
		
		if (is_null($DisplayColor)){
			$DisplayColor = new pColor(0);
		}
		
		$ColorOverride = [];
		
		$this->myPicture->isChartLayoutStacked = FALSE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		if (!empty($OverrideColors)) {
			foreach($OverrideColors as $key => $C){
				$ColorOverride[$key]["Color"] = $C;
				$ColorOverride[$key]["BorderColor"] = (!is_null($OverrideSurrounding)) ? $C->newOne()->RGBChange($OverrideSurrounding) : $C->newOne();
			}
		}

		$RestoreShadow = $this->myPicture->Shadow;
		$SeriesCount = $this->myPicture->myData->countDrawableSeries();
		$CurrentSerie = 0;
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				$Color = $Serie["Color"]->newOne();
				$Ticks = $Serie["Ticks"];
				if ($DisplayType == DISPLAY_AUTO) {
					$DisplayColor = $Color;
				}

				(!is_null($Surrounding)) AND $BorderColor = $Color->newOne()->RGBChange($Surrounding);
				(!is_null($InnerSurrounding)) AND $InnerBorderColor = $Color->newOne()->RGBChange($InnerSurrounding);
				
				$InnerColor = (is_null($InnerBorderColor)) ? NULL : ["Color" => $InnerBorderColor];
				$Settings = ["Color" => $Color,"BorderColor" => $BorderColor];
				$AxisID = $Serie["Axis"];
				$Mode = $Data["Axis"][$AxisID]["Display"];
				$Format = $Data["Axis"][$AxisID]["Format"];
				$Unit = $Data["Axis"][$AxisID]["Unit"];
				$SerieDescription = (isset($Serie["Description"])) ? $Serie["Description"] : $SerieName;
				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				$Floating0Value = (!is_null($Floating0Value)) ? $Floating0Value : 0;
				$YZero = $this->myPicture->scaleComputeYSingle($Floating0Value, $Serie["Axis"]);

				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {
					($YZero > $this->myPicture->GraphAreaY2 - 1) AND $YZero = $this->myPicture->GraphAreaY2 - 1;
					($YZero < $this->myPicture->GraphAreaY1 + 1) AND $YZero = $this->myPicture->GraphAreaY1 + 1;
					$XStep = ($XDivs == 0) ? 0 : ($this->myPicture->GraphAreaXdiff - $XMargin * 2) / $XDivs;
					$X = $this->myPicture->GraphAreaX1 + $XMargin;
					$Y1 = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaY2 - 1;

					if ($XDivs == 0) {
						$XSize = $this->myPicture->GraphAreaXdiff / ($SeriesCount + $Interleave);
					} else {
						$XSize = ($XStep / ($SeriesCount + $Interleave));
					}

					$XOffset = - ($XSize * $SeriesCount) / 2 + $CurrentSerie * $XSize;
					if ($X + $XOffset <= $this->myPicture->GraphAreaX1) {
						$XOffset = $this->myPicture->GraphAreaX1 - $X + 1;
					}

					$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = $XOffset + $XSize / 2;
					$XSpace = ($Rounded || !is_null($BorderColor)) ? 1 : 0;
					
					$ID = 0;
					foreach($PosArray as $Key => $Y2) {
						if (!is_null($Floating0Serie)) {
							$Value = (isset($Data["Series"][$Floating0Serie]["Data"][$Key])) ? $Data["Series"][$Floating0Serie]["Data"][$Key] : 0;
							$YZero = $this->myPicture->scaleComputeYSingle($Value, $Serie["Axis"]);
							($YZero > $this->myPicture->GraphAreaY2 - 1) AND $YZero = $this->myPicture->GraphAreaY2 - 1;
							($YZero < $this->myPicture->GraphAreaY1 + 1) AND $YZero = $this->myPicture->GraphAreaY1 + 1;
							$Y1 = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaY2 - 1;
						}

						if (!empty($ColorOverride)) {
							if (isset($ColorOverride[$ID])) {
								$Settings = $ColorOverride[$ID];
							} else {
								$Settings = ["Color" => $this->myPicture->myData->getRandomColor()];
							}
						}

						if ($Y2 != VOID) {
							$BarHeight = $Y1 - $Y2;
							if ($Serie["Data"][$Key] == 0) {
								$this->myPicture->drawLine($X + $XOffset + $XSpace, $Y1, $X + $XOffset + $XSize - $XSpace, $Y1, $Settings);
								if ($RecordImageMap) {
									$this->myPicture->addToImageMap("RECT", floor($X + $XOffset + $XSpace) . "," . floor($Y1 - 1) . "," . floor($X + $XOffset + $XSize - $XSpace) . "," . floor($Y1 + 1), $Color->toHTMLColor(), $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
								}
							} else {
								if ($RecordImageMap) {
									$this->myPicture->addToImageMap("RECT", floor($X + $XOffset + $XSpace) . "," . floor($Y1) . "," . floor($X + $XOffset + $XSize - $XSpace) . "," . floor($Y2), $Color->toHTMLColor(), $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
								}

								if ($Rounded){
									$this->myPicture->drawRoundedFilledRectangle($X + $XOffset + $XSpace, $Y1, $X + $XOffset + $XSize - $XSpace, $Y2, $RoundRadius, $Settings);
								} else {
									$this->myPicture->drawFilledRectangle($X + $XOffset + $XSpace, $Y1, $X + $XOffset + $XSize - $XSpace, $Y2, $Settings);
									if (!is_null($InnerColor)) {
										$this->myPicture->drawRectangle($X + $XOffset + $XSpace + 1, min($Y1, $Y2) + 1, $X + $XOffset + $XSize - $XSpace - 1, max($Y1, $Y2) - 1, $InnerColor);
									}

									if ($Gradient) {
										$this->myPicture->Shadow = FALSE;
										if ($GradientMode == GRADIENT_SIMPLE) {
											if ($Serie["Data"][$Key] >= 0) {
												$GradienColor = ["StartColor"=>$GradientEndColor,"EndColor"=>$GradientStartColor];
											} else {
												$GradienColor = ["StartColor"=>$GradientEndColor,"EndColor"=>$GradientStartColor];
											}
											$this->myPicture->drawGradientArea($X + $XOffset + $XSpace, $Y1, $X + $XOffset + $XSize - $XSpace, $Y2, DIRECTION_VERTICAL, $GradienColor);
										} elseif ($GradientMode == GRADIENT_EFFECT_CAN) {
											$XSpan = floor($XSize / 3);	
											$this->myPicture->drawGradientArea($X + $XOffset + $XSpace, $Y1, $X + $XOffset + $XSpan - $XSpace, $Y2, DIRECTION_HORIZONTAL, ["StartColor"=>$GradientEndColor,"EndColor"=>$GradientStartColor]);
											$this->myPicture->drawGradientArea($X + $XOffset + $XSpan + $XSpace, $Y1, $X + $XOffset + $XSize - $XSpace, $Y2, DIRECTION_HORIZONTAL, ["StartColor"=>$GradientStartColor,"EndColor"=>$GradientEndColor]);
										}

										$this->myPicture->Shadow = $RestoreShadow;
									}
								}

								if ($Draw0Line) {
									$Line0Color = ["Color" => new pColor(0,0,0,20)];
									$Line0Width = (abs($Y1 - $Y2) > 3) ? 3 : 1;
									($Y1 - $Y2 < 0) AND $Line0Width = - $Line0Width;
									$this->myPicture->drawFilledRectangle($X + $XOffset + $XSpace, floor($Y1), $X + $XOffset + $XSize - $XSpace, floor($Y1) - $Line0Width, $Line0Color);
									$this->myPicture->drawLine($X + $XOffset + $XSpace, floor($Y1), $X + $XOffset + $XSize - $XSpace, floor($Y1), $Line0Color);
								}
							}

							if ($DisplayValues && $Serie["Data"][$Key] != VOID) {
								($DisplayShadow) AND $this->myPicture->Shadow = TRUE;
								$Caption = $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit);
								$TxtPos = $this->myPicture->getTextBox(0, 0, $DisplayFont, $DisplaySize, 90, $Caption);
								$TxtHeight = $TxtPos[0]["Y"] - $TxtPos[1]["Y"] + $TxtMargin;
								if ($DisplayPos == LABEL_POS_INSIDE && abs($TxtHeight) < abs($BarHeight)) {
									$CenterX = (($X + $XOffset + $XSize - $XSpace) - ($X + $XOffset + $XSpace)) / 2 + $X + $XOffset + $XSpace;
									$CenterY = ($Y2 - $Y1) / 2 + $Y1;
									$this->myPicture->drawText($CenterX, $CenterY, $Caption, ["Color" => $DisplayColor,"Align" => TEXT_ALIGN_MIDDLEMIDDLE,"FontSize" => $DisplaySize,"Angle" => 90]);
								} else {
									if ($Serie["Data"][$Key] >= 0) {
										$Align = TEXT_ALIGN_BOTTOMMIDDLE;
										$Offset = $DisplayOffset;
									} else {
										$Align = TEXT_ALIGN_TOPMIDDLE;
										$Offset = - $DisplayOffset;
									}
									$this->myPicture->drawText($X + $XOffset + $XSize / 2, $Y2 - $Offset, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), ["Color" => $DisplayColor,"Align" => $Align,"FontSize" => $DisplaySize]);
								}

								$this->myPicture->Shadow = $RestoreShadow;
							}
						}

						$X += $XStep;
						$ID++;
					}
					
				} else {
					
					($YZero < $this->myPicture->GraphAreaX1 + 1) AND $YZero = $this->myPicture->GraphAreaX1 + 1;
					($YZero > $this->myPicture->GraphAreaX2 - 1) AND $YZero = $this->myPicture->GraphAreaX2 - 1;
					$YStep = ($XDivs == 0) ? 0 : ($this->myPicture->GraphAreaYdiff - $XMargin * 2) / $XDivs;
					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
					$X1 = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaX1 + 1;

					if ($XDivs == 0) {
						$YSize = $this->myPicture->GraphAreaYdiff / ($SeriesCount + $Interleave);
					} else {
						$YSize = ($YStep / ($SeriesCount + $Interleave));
					}

					$YOffset = - ($YSize * $SeriesCount) / 2 + $CurrentSerie * $YSize;
					if ($Y + $YOffset <= $this->myPicture->GraphAreaY1) {
						$YOffset = $this->myPicture->GraphAreaY1 - $Y + 1;
					}

					$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = $YOffset + $YSize / 2;
					$YSpace = ($Rounded || !is_null($BorderColor)) ? 1 : 0;
					
					$ID = 0;
					foreach($PosArray as $Key => $X2) {
						if (!is_null($Floating0Serie)) {
							$Value = (isset($Data["Series"][$Floating0Serie]["Data"][$Key])) ? $Data["Series"][$Floating0Serie]["Data"][$Key] : 0;
							$YZero = $this->myPicture->scaleComputeYSingle($Value, $Serie["Axis"]);
							($YZero < $this->myPicture->GraphAreaX1 + 1) AND $YZero = $this->myPicture->GraphAreaX1 + 1;
							($YZero > $this->myPicture->GraphAreaX2 - 1) AND $YZero = $this->myPicture->GraphAreaX2 - 1;
							$X1 = ($AroundZero) ? $YZero : $this->myPicture->GraphAreaX1 + 1;
						}

						if (!empty($ColorOverride)) {
							if (isset($ColorOverride[$ID])) {
								$Settings = $ColorOverride[$ID];
							} else {
								$Settings = ["Color" => $this->myPicture->myData->getRandomColor()];
							}
						}

						if ($X2 != VOID) {
							$BarWidth = $X2 - $X1;
							if ($Serie["Data"][$Key] == 0) {
								$this->myPicture->drawLine($X1, $Y + $YOffset + $YSpace, $X1, $Y + $YOffset + $YSize - $YSpace, $Settings);
								if ($RecordImageMap) {
									$this->myPicture->addToImageMap("RECT", floor($X1 - 1) . "," . floor($Y + $YOffset + $YSpace) . "," . floor($X1 + 1) . "," . floor($Y + $YOffset + $YSize - $YSpace), $Color->toHTMLColor(), $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
								}
							} else {
								if ($RecordImageMap) {
									$this->myPicture->addToImageMap("RECT", floor($X1) . "," . floor($Y + $YOffset + $YSpace) . "," . floor($X2) . "," . floor($Y + $YOffset + $YSize - $YSpace), $Color->toHTMLColor(), $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
								}

								if ($Rounded) {
									$this->myPicture->drawRoundedFilledRectangle($X1 + 1, $Y + $YOffset + $YSpace, $X2, $Y + $YOffset + $YSize - $YSpace, $RoundRadius, $Settings);
								} else {
									$this->myPicture->drawFilledRectangle($X1, $Y + $YOffset + $YSpace, $X2, $Y + $YOffset + $YSize - $YSpace, $Settings);
									if (!is_null($InnerColor)) {
										$this->myPicture->drawRectangle(min($X1, $X2) + 1, $Y + $YOffset + $YSpace + 1, max($X1, $X2) - 1, $Y + $YOffset + $YSize - $YSpace - 1, $InnerColor);
									}

									if ($Gradient) {
										$this->myPicture->Shadow = FALSE;
										if ($GradientMode == GRADIENT_SIMPLE) {
											if ($Serie["Data"][$Key] >= 0) {
												$GradienColor = ["StartColor"=>$GradientStartColor,"EndColor"=>$GradientEndColor];
											} else {
												$GradienColor = ["StartColor"=>$GradientEndColor,"EndColor"=>$GradientStartColor];
											}

											$this->myPicture->drawGradientArea($X1, $Y + $YOffset + $YSpace, $X2, $Y + $YOffset + $YSize - $YSpace, DIRECTION_HORIZONTAL, $GradienColor);
										} elseif ($GradientMode == GRADIENT_EFFECT_CAN) {
											$YSpan = floor($YSize / 3);
											$this->myPicture->drawGradientArea($X1, $Y + $YOffset + $YSpace, $X2, $Y + $YOffset + $YSpan - $YSpace, DIRECTION_VERTICAL, ["StartColor"=>$GradientEndColor,"EndColor"=>$GradientStartColor]);
											$this->myPicture->drawGradientArea($X1, $Y + $YOffset + $YSpan, $X2, $Y + $YOffset + $YSize - $YSpace, DIRECTION_VERTICAL, ["StartColor"=>$GradientStartColor,"EndColor"=>$GradientEndColor]);
										}

										$this->myPicture->Shadow = $RestoreShadow;
									}
								}

								if ($Draw0Line) {
									$Line0Color = new pColor(0,0,0,20);
									$Line0Width = (abs($X1 - $X2) > 3) ? 3 : 1;
									($X2 - $X1 < 0) AND $Line0Width = - $Line0Width;
									$this->myPicture->drawFilledRectangle(floor($X1), $Y + $YOffset + $YSpace, floor($X1) + $Line0Width, $Y + $YOffset + $YSize - $YSpace, $Line0Color);
									$this->myPicture->drawLine(floor($X1), $Y + $YOffset + $YSpace, floor($X1), $Y + $YOffset + $YSize - $YSpace, $Line0Color);
								}
							}

							if ($DisplayValues && $Serie["Data"][$Key] != VOID) {
								($DisplayShadow) AND $this->myPicture->Shadow = TRUE;
								$Caption = $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit);
								$TxtPos = $this->myPicture->getTextBox(0, 0, $DisplayFont, $DisplaySize, 0, $Caption);
								$TxtWidth = $TxtPos[1]["X"] - $TxtPos[0]["X"] + $TxtMargin;
								if ($DisplayPos == LABEL_POS_INSIDE && abs($TxtWidth) < abs($BarWidth)) {
									$CenterX = ($X2 - $X1) / 2 + $X1;
									$CenterY = (($Y + $YOffset + $YSize - $YSpace) - ($Y + $YOffset + $YSpace)) / 2 + ($Y + $YOffset + $YSpace);
									$this->myPicture->drawText($CenterX, $CenterY, $Caption, ["Color" => $DisplayColor,"Align" => TEXT_ALIGN_MIDDLEMIDDLE,"FontSize" => $DisplaySize]);
								} else {
									if ($Serie["Data"][$Key] >= 0) {
										$Align = TEXT_ALIGN_MIDDLELEFT;
										$Offset = $DisplayOffset;
									} else {
										$Align = TEXT_ALIGN_MIDDLERIGHT;
										$Offset = - $DisplayOffset;
									}

									$this->myPicture->drawText($X2 + $Offset, $Y + $YOffset + $YSize / 2, $Caption, ["Color" => $DisplayColor,"Align" => $Align,"FontSize" => $DisplaySize]);
								}

								$this->myPicture->Shadow = $RestoreShadow;
							}
						}

						$Y += $YStep;
						$ID++;
					}
				}

				$CurrentSerie++;
			}
		}
	}

	/* Draw a bar chart */
	function drawStackedBarChart(array $Format = [])
	{
		$DisplayValues = FALSE;
		$DisplayOrientation = ORIENTATION_AUTO;
		$DisplayRound = 0;
		$DisplayType = DISPLAY_MANUAL;
		$DisplayFont = $this->myPicture->FontName;
		$DisplaySize = $this->myPicture->FontSize;
		$DisplayColor = new pColor(0);
		$Interleave = .5;
		$Rounded = FALSE;
		$RoundRadius = 4;
		$Surrounding = NULL;
		$BorderColor = NULL;
		$Gradient = FALSE;
		$GradientMode = GRADIENT_SIMPLE;
		$GradientStartColor = new pColor(255,255,255,20);
		$GradientEndColor = new pColor(0,0,0,20);
		$InnerSurrounding = NULL;
		$InnerBorderColor = NULL;
		$RecordImageMap = FALSE;
		$FontFactor = 8;
		
		/* Override defaults */
		extract($Format);
		
		$this->myPicture->isChartLayoutStacked = TRUE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		$RestoreShadow = $this->myPicture->Shadow;
		$LastX = [];
		$LastY = [];
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				$Color = $Serie["Color"]->newOne();
				$Ticks = $Serie["Ticks"];
				if ($DisplayType == DISPLAY_AUTO) {
					$DisplayColor = new pColor(255);
				}

				(!is_null($Surrounding)) AND $BorderColor = $Color->newOne()->RGBChange($Surrounding);
				(!is_null($InnerSurrounding)) AND $InnerBorderColor = $Color->newOne()->RGBChange($InnerSurrounding);

				$InnerColor = (is_null($InnerBorderColor)) ? NULL : ["Color" => $InnerBorderColor];
				$AxisID = $Serie["Axis"];
				$Mode = $Data["Axis"][$AxisID]["Display"];
				$Format = $Data["Axis"][$AxisID]["Format"];
				$Unit = $Data["Axis"][$AxisID]["Unit"];
				$PosArray = $this->myPicture->scaleComputeY0HeightOnly($Serie["Data"], $Serie["Axis"]);
				$YZero = $this->myPicture->scaleComputeYSingle(0, $Serie["Axis"]);
				$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = 0;
				$RectangleSettings = ["TransCorner" => TRUE,"Color" => $Color,"BorderColor" => $BorderColor];
				
				if ($RecordImageMap) {
					$SerieDescription = (isset($Serie["Description"])) ? $Serie["Description"] : $SerieName;
					$ImageMapColor = $RectangleSettings["Color"]->toHTMLColor();
				}
				
				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);
				
				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {
					($YZero > $this->myPicture->GraphAreaY2 - 1) AND $YZero = $this->myPicture->GraphAreaY2 - 1;
					($YZero > $this->myPicture->GraphAreaY2 - 1) AND $YZero = $this->myPicture->GraphAreaY2 - 1;
					
					$X = $this->myPicture->GraphAreaX1 + $XMargin;
					$XSize = ($XStep / (1 + $Interleave));
					$XOffset = - ($XSize / 2);

					foreach($PosArray as $Key => $Height) {
						if ($Height != VOID && $Serie["Data"][$Key] != 0) {
							$Pos = ($Serie["Data"][$Key] > 0) ? "+" : "-";
	
							(!isset($LastY[$Key])) AND $LastY[$Key] = [];
							(!isset($LastY[$Key][$Pos])) AND $LastY[$Key][$Pos] = $YZero;
							
							$Y1 = $LastY[$Key][$Pos];
							$Y2 = $Y1 - $Height;
							$YSpaceUp = (($Rounded || !is_null($BorderColor)) && ($Pos == "+" && $Y1 != $YZero)) ? 1 : 0;
							$YSpaceDown = (($Rounded || !is_null($BorderColor)) && ($Pos == "-" && $Y1 != $YZero)) ? 1 : 0;

							if ($RecordImageMap) {
								$this->myPicture->addToImageMap("RECT", floor($X + $XOffset) . "," . floor($Y1 - $YSpaceUp + $YSpaceDown) . "," . floor($X + $XOffset + $XSize) . "," . floor($Y2), $ImageMapColor, $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}

							if ($Rounded) {
								$this->myPicture->drawRoundedFilledRectangle($X + $XOffset, $Y1 - $YSpaceUp + $YSpaceDown, $X + $XOffset + $XSize, $Y2, $RoundRadius, $RectangleSettings);
							} else {
								$this->myPicture->drawFilledRectangle($X + $XOffset, $Y1 - $YSpaceUp + $YSpaceDown, $X + $XOffset + $XSize, $Y2, $RectangleSettings);
								if (!is_null($InnerColor)) {
									$RestoreShadow = $this->myPicture->Shadow;
									$this->myPicture->Shadow = FALSE;
									$this->myPicture->drawRectangle(min($X + $XOffset + 1, $X + $XOffset + $XSize), min($Y1 - $YSpaceUp + $YSpaceDown, $Y2) + 1, max($X + $XOffset + 1, $X + $XOffset + $XSize) - 1, max($Y1 - $YSpaceUp + $YSpaceDown, $Y2) - 1, $InnerColor);
									$this->myPicture->Shadow = $RestoreShadow;
								}

								if ($Gradient) {
									$this->myPicture->Shadow = FALSE;
									if ($GradientMode == GRADIENT_SIMPLE) {
										$this->myPicture->drawGradientArea($X + $XOffset, $Y1 - 1 - $YSpaceUp + $YSpaceDown, $X + $XOffset + $XSize, $Y2 + 1, DIRECTION_VERTICAL, ["StartColor"=>$GradientStartColor,"EndColor"=>$GradientEndColor]);
									} elseif ($GradientMode == GRADIENT_EFFECT_CAN) {
										$XSpan = floor($XSize / 3);
										$this->myPicture->drawGradientArea($X + $XOffset - .5, $Y1 - .5 - $YSpaceUp + $YSpaceDown, $X + $XOffset + $XSpan, $Y2 + .5, DIRECTION_HORIZONTAL, ["StartColor"=>$GradientEndColor,"EndColor"=>$GradientStartColor]);
										$this->myPicture->drawGradientArea($X + $XSpan + $XOffset - .5, $Y1 - .5 - $YSpaceUp + $YSpaceDown, $X + $XOffset + $XSize, $Y2 + .5, DIRECTION_HORIZONTAL, ["StartColor"=>$GradientStartColor,"EndColor"=>$GradientEndColor]);
									}

									$this->myPicture->Shadow = $RestoreShadow;
								}
							}

							if ($DisplayValues) {
								$BarHeight = abs($Y2 - $Y1) - 2;
								$BarWidth = $XSize + ($XOffset / 2) - $FontFactor;
								$Caption = $this->myPicture->scaleFormat(round($Serie["Data"][$Key], $DisplayRound), $Mode, $Format, $Unit);
								$TxtPos = $this->myPicture->getTextBox(0, 0, $DisplayFont, $DisplaySize, 0, $Caption);
								$TxtHeight = abs($TxtPos[2]["Y"] - $TxtPos[0]["Y"]);
								$TxtWidth = abs($TxtPos[1]["X"] - $TxtPos[0]["X"]);
								$XCenter = (($X + $XOffset + $XSize) - ($X + $XOffset)) / 2 + $X + $XOffset;
								$YCenter = (($Y2) - ($Y1 - $YSpaceUp + $YSpaceDown)) / 2 + $Y1 - $YSpaceUp + $YSpaceDown;
								$Done = FALSE;
								if ($DisplayOrientation == ORIENTATION_HORIZONTAL || $DisplayOrientation == ORIENTATION_AUTO) {
									if ($TxtHeight < $BarHeight && $TxtWidth < $BarWidth) {
										# Momchil: +1 is a visual fix for example.drawStackedBarChart.rounded. Probably a bug introduced by refactoring
										$this->myPicture->drawText($XCenter, $YCenter+1, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), ["Color" => $DisplayColor,"Align" => TEXT_ALIGN_MIDDLEMIDDLE,"FontSize" => $DisplaySize,"FontName" => $DisplayFont]);
										$Done = TRUE;
									}
								}

								if ($DisplayOrientation == ORIENTATION_VERTICAL || ($DisplayOrientation == ORIENTATION_AUTO && !$Done)) {
									if ($TxtHeight < $BarWidth && $TxtWidth < $BarHeight) {
										$this->myPicture->drawText($XCenter, $YCenter, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), ["Color" => $DisplayColor,"Angle" => 90,"Align" => TEXT_ALIGN_MIDDLEMIDDLE,"FontSize" => $DisplaySize,"FontName" => $DisplayFont]);
									}
								}
							}

							$LastY[$Key][$Pos] = $Y2;
						}

						$X += $XStep;
					}
				} else { # SCALE_POS_LEFTRIGHT
				
					($YZero < $this->myPicture->GraphAreaX1 + 1) AND $YZero = $this->myPicture->GraphAreaX1 + 1;
					($YZero > $this->myPicture->GraphAreaX2 - 1) AND $YZero = $this->myPicture->GraphAreaX2 - 1;

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
					$YSize = $XStep / (1 + $Interleave);
					$YOffset = - ($YSize / 2);
					
					foreach($PosArray as $Key => $Width) {
						if ($Width != VOID && $Serie["Data"][$Key] != 0) {
							$Pos = ($Serie["Data"][$Key] > 0) ? "+" : "-";
							(!isset($LastX[$Key])) AND $LastX[$Key] = [];
							(!isset($LastX[$Key][$Pos])) AND $LastX[$Key][$Pos] = $YZero;
							$X1 = $LastX[$Key][$Pos];
							$X2 = $X1 + $Width;
							$XSpaceLeft = (($Rounded || !is_null($BorderColor)) && ($Pos == "+" && $X1 != $YZero)) ? 2 : 0;
							$XSpaceRight = (($Rounded || !is_null($BorderColor)) && ($Pos == "-" && $X1 != $YZero)) ? 2 : 0;
							
							if ($RecordImageMap) {
								$this->myPicture->addToImageMap("RECT", floor($X1 + $XSpaceLeft) . "," . floor($Y + $YOffset) . "," . floor($X2 - $XSpaceRight) . "," . floor($Y + $YOffset + $YSize), $ImageMapColor, $SerieDescription, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit));
							}

							if ($Rounded) {
								$this->myPicture->drawRoundedFilledRectangle($X1 + $XSpaceLeft, $Y + $YOffset, $X2 - $XSpaceRight, $Y + $YOffset + $YSize, $RoundRadius, $RectangleSettings);
							} else {
								$this->myPicture->drawFilledRectangle($X1 + $XSpaceLeft, $Y + $YOffset, $X2 - $XSpaceRight, $Y + $YOffset + $YSize, $RectangleSettings);
								if (!is_null($InnerColor)) {
									$RestoreShadow = $this->myPicture->Shadow;
									$this->myPicture->Shadow = FALSE;
									$this->myPicture->drawRectangle(min($X1 + $XSpaceLeft, $X2 - $XSpaceRight) + 1, min($Y + $YOffset, $Y + $YOffset + $YSize) + 1, max($X1 + $XSpaceLeft, $X2 - $XSpaceRight) - 1, max($Y + $YOffset, $Y + $YOffset + $YSize) - 1, $InnerColor);
									$this->myPicture->Shadow = $RestoreShadow;
								}

								if ($Gradient) {
									$this->myPicture->Shadow = FALSE;
									if ($GradientMode == GRADIENT_SIMPLE) {
										$this->myPicture->drawGradientArea($X1 + $XSpaceLeft, $Y + $YOffset, $X2 - $XSpaceRight, $Y + $YOffset + $YSize, DIRECTION_HORIZONTAL, ["StartColor"=>$GradientStartColor,"EndColor"=>$GradientEndColor]);
									} elseif ($GradientMode == GRADIENT_EFFECT_CAN) {
										$YSpan = floor($YSize / 3);
										$this->myPicture->drawGradientArea($X1 + $XSpaceLeft, $Y + $YOffset, $X2 - $XSpaceRight, $Y + $YOffset + $YSpan, DIRECTION_VERTICAL, ["StartColor"=>$GradientEndColor,"EndColor"=>$GradientStartColor]);
										$this->myPicture->drawGradientArea($X1 + $XSpaceLeft, $Y + $YOffset + $YSpan, $X2 - $XSpaceRight, $Y + $YOffset + $YSize, DIRECTION_VERTICAL, ["StartColor"=>$GradientStartColor,"EndColor"=>$GradientEndColor]);
									}

									$this->myPicture->Shadow = $RestoreShadow;
								}
							}

							if ($DisplayValues) {
								$BarWidth = abs($X2 - $X1) - $FontFactor;
								$BarHeight = $YSize + ($YOffset / 2) - $FontFactor / 2;
								$Caption = $this->myPicture->scaleFormat(round($Serie["Data"][$Key], $DisplayRound), $Mode, $Format, $Unit);
								$TxtPos = $this->myPicture->getTextBox(0, 0, $DisplayFont, $DisplaySize, 0, $Caption);
								$TxtHeight = abs($TxtPos[2]["Y"] - $TxtPos[0]["Y"]);
								$TxtWidth = abs($TxtPos[1]["X"] - $TxtPos[0]["X"]);
								$XCenter = ($X2 - $X1) / 2 + $X1;
								$YCenter = (($Y + $YOffset + $YSize) - ($Y + $YOffset)) / 2 + $Y + $YOffset;
								$Done = FALSE;
								if ($DisplayOrientation == ORIENTATION_HORIZONTAL || $DisplayOrientation == ORIENTATION_AUTO) {
									if ($TxtHeight < $BarHeight && $TxtWidth < $BarWidth) {
										$this->myPicture->drawText($XCenter, $YCenter, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), ["Color" => $DisplayColor,"Align" => TEXT_ALIGN_MIDDLEMIDDLE,"FontSize" => $DisplaySize,"FontName" => $DisplayFont]);
										$Done = TRUE;
									}
								}

								if ($DisplayOrientation == ORIENTATION_VERTICAL || ($DisplayOrientation == ORIENTATION_AUTO && !$Done)) {
									if ($TxtHeight < $BarWidth && $TxtWidth < $BarHeight) {
										$this->myPicture->drawText($XCenter, $YCenter, $this->myPicture->scaleFormat($Serie["Data"][$Key], $Mode, $Format, $Unit), ["Color" => $DisplayColor,"Angle" => 90,"Align" => TEXT_ALIGN_MIDDLEMIDDLE,"FontSize" => $DisplaySize,"FontName" => $DisplayFont]);
									}
								}
							}

							$LastX[$Key][$Pos] = $X2;
						}

						$Y += $XStep;
					}
				}
			}
		}
	} 

	/* Draw a stacked area chart */
	function drawStackedAreaChart(array $Format = [])
	{
		$DrawLine = FALSE;
		$LineSurrounding = NULL;
		$LineColor = NULL;
		$DrawPlot = FALSE;
		$PlotRadius = 2;
		$PlotBorder = 1;
		$PlotBorderSurrounding = NULL;
		$PlotBorderColor = new pColor(0,0,0,50);
		$ForceTransparency = NULL;
		
		/* Override defaults */
		extract($Format);
		
		$this->myPicture->isChartLayoutStacked = TRUE;
		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		$RestoreShadow = $this->myPicture->Shadow;
		$this->myPicture->Shadow = FALSE;
		
		/* Build the offset data series */
		$OverallOffset = [];
		$SerieOrder = [];
		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				$SerieOrder[] = $SerieName;
				foreach($Serie["Data"] as $Key => $Value) {
					($Value == VOID) AND $Value = 0;
					$Sign = ($Value >= 0) ? "+" : "-";
					(!isset($OverallOffset[$Key]) || !isset($OverallOffset[$Key][$Sign])) AND $OverallOffset[$Key][$Sign] = 0;
					$Data["Series"][$SerieName]["Data"][$Key] = ($Sign == "+") ? $Value + $OverallOffset[$Key][$Sign] : $Value - $OverallOffset[$Key][$Sign];
					$OverallOffset[$Key][$Sign] = $OverallOffset[$Key][$Sign] + abs($Value);
				}
			}
		}

		$SerieOrder = array_reverse($SerieOrder);

		foreach($SerieOrder as $SerieName) {
			$Serie = $Data["Series"][$SerieName];
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {
				
				$Color = $Serie["Color"]->newOne();
				(!is_null($ForceTransparency)) AND $Color->Alpha = $ForceTransparency;
				$Settings = ["Color" => $Color];
				
				if (!is_null($LineSurrounding)) {
					$LineSettings = ["Color" => $Color->newOne()->RGBChange($LineSurrounding)];
				} elseif (!is_null($LineColor)) {
					$LineSettings = ["Color" => $LineColor];
				} else {
					$LineSettings = $Settings;
				}

				$PlotBorderSettings = ["Color" => (!is_null($PlotBorderSurrounding)) ? $Color->newOne()->RGBChange($PlotBorderSurrounding) : $PlotBorderColor];
				$PosArray = $this->myPicture->scaleComputeY0HeightOnly($Serie["Data"], $Serie["Axis"]);
				$YZero = $this->myPicture->scaleComputeYSingle(0, $Serie["Axis"]);
				$this->myPicture->myData->Data["Series"][$SerieName]["XOffset"] = 0;
				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);
				
				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {
					($YZero < $this->myPicture->GraphAreaY1 + 1) AND $YZero = $this->myPicture->GraphAreaY1 + 1;
					($YZero > $this->myPicture->GraphAreaY2 - 1) AND $YZero = $this->myPicture->GraphAreaY2 - 1;

					$X = $this->myPicture->GraphAreaX1 + $XMargin;
					
					$Plots = [$X, $YZero];

					foreach($PosArray as $Height) {
						if ($Height != VOID) {
							$Plots[] = $X;
							$Plots[] = $YZero - $Height;
						}

						$X += $XStep;
					}

					$Plots[] = $X - $XStep;
					$Plots[] = $YZero;
					$this->myPicture->drawPolygon($Plots, $Settings);
					$this->myPicture->Shadow = $RestoreShadow;
					if ($DrawLine) {
						for ($i = 2; $i <= count($Plots) - 6; $i = $i + 2) {
							$this->myPicture->drawLine($Plots[$i], $Plots[$i + 1], $Plots[$i + 2], $Plots[$i + 3], $LineSettings);
						}
					}

					if ($DrawPlot) {
						for ($i = 2; $i <= count($Plots) - 4; $i = $i + 2) {
							if ($PlotBorder != 0) {
								$this->myPicture->drawFilledCircle($Plots[$i], $Plots[$i + 1], $PlotRadius + $PlotBorder, $PlotBorderSettings);
							}

							$this->myPicture->drawFilledCircle($Plots[$i], $Plots[$i + 1], $PlotRadius, $Settings);
						}
					}

					$this->myPicture->Shadow = FALSE;
					
				} elseif ($Data["Orientation"] == SCALE_POS_TOPBOTTOM) {
					($YZero < $this->myPicture->GraphAreaX1 + 1) AND $YZero = $this->myPicture->GraphAreaX1 + 1;
					($YZero > $this->myPicture->GraphAreaX2 - 1) AND $YZero = $this->myPicture->GraphAreaX2 - 1;

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
					
					$Plots = [$YZero, $Y];
					foreach($PosArray as $Height) {
						if ($Height != VOID) {
							$Plots[] = $YZero + $Height;
							$Plots[] = $Y;
						}

						$Y += $XStep;
					}

					$Plots[] = $YZero;
					$Plots[] = $Y - $XStep;
					$this->myPicture->drawPolygon($Plots, $Settings);
					$this->myPicture->Shadow = $RestoreShadow;
					if ($DrawLine) {
						for ($i = 2; $i <= count($Plots) - 6; $i = $i + 2) {
							$this->myPicture->drawLine($Plots[$i], $Plots[$i + 1], $Plots[$i + 2], $Plots[$i + 3], $LineSettings);
						}
					}

					if ($DrawPlot) {
						for ($i = 2; $i <= count($Plots) - 4; $i = $i + 2) {
							if ($PlotBorder != 0) {
								$this->myPicture->drawFilledCircle($Plots[$i], $Plots[$i + 1], $PlotRadius + $PlotBorder, $PlotBorderSettings);
							}

							$this->myPicture->drawFilledCircle($Plots[$i], $Plots[$i + 1], $PlotRadius, $Settings);
						}
					}

					$this->myPicture->Shadow = FALSE;
				}
			}
		}

		$this->myPicture->Shadow = $RestoreShadow;
	}

	function drawPolygonChart(array $Points, array $Format = [])
	{
		$DefaultColor = isset($Format["Color"]) ? $Format["Color"] : new pColor(0);
		$Threshold = isset($Format["Threshold"]) ? $Format["Threshold"] : [];
		$NoFill = isset($Format["NoFill"]) ? $Format["NoFill"] : FALSE;
		$NoBorder = isset($Format["NoBorder"]) ? $Format["NoBorder"] : FALSE;
		$BorderColor = isset($Format["BorderColor"]) ? $Format["BorderColor"] : $DefaultColor->newOne()->AlphaSlash(2);
		if (isset($Format["Surrounding"])){
			$BorderColor = $DefaultColor->newOne()->RGBChange($Format["Surrounding"]);
		}
				
		$RestoreShadow = $this->myPicture->Shadow;
		$this->myPicture->Shadow = FALSE;
		$AllIntegers = TRUE;
		for ($i = 0; $i <= count($Points) - 2; $i = $i + 2) {
			if ($this->myPicture->getFirstDecimal($Points[$i + 1]) != 0) {
				$AllIntegers = FALSE;
			}
		}

		/* Convert polygon to segments */
		$Segments = [];
		for ($i = 2; $i <= count($Points) - 2; $i = $i + 2) {
			$Segments[] = ["X1" => $Points[$i - 2],"Y1" => $Points[$i - 1],"X2" => $Points[$i],"Y2" => $Points[$i + 1]];
		}

		$Segments[] = ["X1" => $Points[$i - 2],"Y1" => $Points[$i - 1],"X2" => $Points[0],"Y2" => $Points[1]];
		/* Simplify straight lines */
		$Result = [];
		$inHorizon = FALSE;
		$LastX = VOID;
		foreach($Segments as $Pos) {
			if ($Pos["Y1"] != $Pos["Y2"]) {
				if ($inHorizon) {
					$inHorizon = FALSE;
					$Result[] = ["X1" => $LastX,"Y1" => $Pos["Y1"],"X2" => $Pos["X1"],"Y2" => $Pos["Y1"]];
				}

				$Result[] = ["X1" => $Pos["X1"],"Y1" => $Pos["Y1"],"X2" => $Pos["X2"],"Y2" => $Pos["Y2"]];
			} else {
				if (!$inHorizon) {
					$inHorizon = TRUE;
					$LastX = $Pos["X1"];
				}
			}
		}

		$Segments = $Result;
		/* Do we have something to draw */
		if (empty($Segments)) {
			return; # Momchil That's OK
		}

		/* Find out the min & max Y boundaries */
		$MinY = OUT_OF_SIGHT;
		$MaxY = OUT_OF_SIGHT;
		foreach($Segments as $Coords) {
			if ($MinY == OUT_OF_SIGHT || $MinY > min($Coords["Y1"], $Coords["Y2"])) {
				$MinY = min($Coords["Y1"], $Coords["Y2"]);
			}

			if ($MaxY == OUT_OF_SIGHT || $MaxY < max($Coords["Y1"], $Coords["Y2"])) {
				$MaxY = max($Coords["Y1"], $Coords["Y2"]);
			}
		}

		$YStep = ($AllIntegers) ? 1 : .5;
		$MinY = floor($MinY);
		$MaxY = floor($MaxY);
		/* Scan each Y lines */
		$MinY = floor($MinY);
		$MaxY = floor($MaxY);
		$YStep = 1;
		if (!$NoFill) {

			for ($Y = $MinY; $Y <= $MaxY; $Y = $Y + $YStep) {
				$Intersections = [];
				$LastSlope = NULL;
				foreach($Segments as $Coords) {
					$X1 = $Coords["X1"];
					$X2 = $Coords["X2"];
					$Y1 = $Coords["Y1"];
					$Y2 = $Coords["Y2"];
					if (min($Y1, $Y2) <= $Y && max($Y1, $Y2) >= $Y) {
						if ($Y1 == $Y2) {
							$X = $X1;
						} else {
							$X = $X1 + (($Y - $Y1) * $X2 - ($Y - $Y1) * $X1) / ($Y2 - $Y1);
						}

						$X = floor($X);
						if ($X2 == $X1) {
							$Slope = "!";
						} else {
							$SlopeC = ($Y2 - $Y1) / ($X2 - $X1);
							if ($SlopeC == 0) {
								$Slope = "=";
							} elseif ($SlopeC > 0) {
								$Slope = "+";
							} elseif ($SlopeC < 0) {
								$Slope = "-";
							}
						}

						if (!in_array($X, $Intersections)) {
							$Intersections[] = $X;
						} else {

							if ($Slope == "=" && $LastSlope == "-") {
								$Intersections[] = $X;
							}

							if ($Slope != $LastSlope && $LastSlope != "!" && $LastSlope != "=") {
								$Intersections[] = $X;
							}

							if ($Slope != $LastSlope && $LastSlope == "!" && $Slope == "+") {
								$Intersections[] = $X;
							}
							
							if ($LastSlope == "=" && $Slope == "-") {
								$Intersections[] = $X;
							}
						}

						$LastSlope = $Slope;
					}
				}

				sort($Intersections);

				/* Remove NULL plots */
				$Result = [];
				for ($i = 0; $i <= count($Intersections) - 1; $i = $i + 2) {
					if (isset($Intersections[$i + 1])) {
						if ($Intersections[$i] != $Intersections[$i + 1]) {
							$Result[] = $Intersections[$i];
							$Result[] = $Intersections[$i + 1];
						}
					}
				}

				$LastX = OUT_OF_SIGHT;
				foreach($Result as $X) {
					if ($LastX == OUT_OF_SIGHT) {
						$LastX = $X;
					} else {
						if ($this->myPicture->getFirstDecimal($LastX) > 1) {
							$LastX++;
						}

						$Color = $DefaultColor->newOne();

						foreach($Threshold as $Parameters) {
							if ($Y <= $Parameters["MinX"] && $Y >= $Parameters["MaxX"]) {
								$Color = $Parameters['Color']->newOne();
							}
						}

						$this->myPicture->drawLine($LastX, $Y, $X, $Y, ["Color" => $Color]);
						
						$LastX = OUT_OF_SIGHT;
					}
				}

			}
		} # No Fill

		/* Draw the polygon border, if required */
		if (!$NoBorder) {
			foreach($Segments as $Coords) {
				$this->myPicture->drawLine($Coords["X1"], $Coords["Y1"], $Coords["X2"], $Coords["Y2"], ["Color" => $BorderColor,"Threshold" => $Threshold]);
			}
		}

		$this->myPicture->Shadow = $RestoreShadow;
	}
	
	/* Create the encoded string */
	function drawSplitPath(array $Format = [])
	{
		$Spacing = isset($Format["Spacing"]) ? $Format["Spacing"] : 20;
		$TextPadding = isset($Format["TextPadding"]) ? $Format["TextPadding"] : 2;
		$TextPos = isset($Format["TextPos"]) ? $Format["TextPos"] : TEXT_POS_TOP;
		$Surrounding = isset($Format["Surrounding"]) ? $Format["Surrounding"] : NULL;
		$Force = isset($Format["Force"]) ? $Format["Force"] : 70;
		$Segments = isset($Format["Segments"]) ? $Format["Segments"] : 15;

		$X1 = $this->myPicture->GraphAreaX1;
		$Y1 = $this->myPicture->GraphAreaY1;
		$X2 = $this->myPicture->GraphAreaX2;
		$Y2 = $this->myPicture->GraphAreaY2;
		/* Data Processing */
		$Data = $this->myPicture->myData->Data;
		$LabelSerie = $Data["Abscissa"];
		$DataSerie = NULL;

		foreach(array_keys($Data["Series"]) as $SerieName) {
			if ($SerieName != $LabelSerie) {
				$DataSerie = $SerieName;
				break;
			}
		}
		
		if (is_null($DataSerie)){
			throw pException::InvalidInput("Invalid data serie");
		}

		$DataSerieSum = array_sum($Data["Series"][$DataSerie]["Data"]);
		$DataSerieCount = count($Data["Series"][$DataSerie]["Data"]);
		/* Scale Processing */
		if ($TextPos == TEXT_POS_RIGHT) {
			$YScale = (($Y2 - $Y1) - (($DataSerieCount + 1) * $Spacing)) / $DataSerieSum;
		} else {
			$YScale = (($Y2 - $Y1) - ($DataSerieCount * $Spacing)) / $DataSerieSum;
		}

		$LeftHeight = $DataSerieSum * $YScale;
		/* Re-compute graph width depending of the text mode chosen */
		if ($TextPos == TEXT_POS_RIGHT) {
			$MaxWidth = 0;
			foreach($Data["Series"][$LabelSerie]["Data"] as $Label) {
				$Boundardies = $this->myPicture->getTextBox(0, 0, $this->myPicture->FontName, $this->myPicture->FontSize, 0, $Label);
				if ($Boundardies[1]["X"] > $MaxWidth) {
					$MaxWidth = $Boundardies[1]["X"] + $TextPadding * 2;
				}
			}

			$X2 = $X2 - $MaxWidth;
		}

		/* Drawing */
		$LeftY = ((($Y2 - $Y1) / 2) + $Y1) - ($LeftHeight / 2);
		$RightY = $Y1;
		# $VectorX = (($X2 - $X1) / 2); # UNUSED
		
		foreach($Data["Series"][$DataSerie]["Data"] as $Key => $Value) {

			$Label = (isset($Data["Series"][$LabelSerie]["Data"][$Key])) ? $Data["Series"][$LabelSerie]["Data"][$Key] : "-";
			
			$LeftY1 = $LeftY;
			$LeftY2 = $LeftY + $Value * $YScale;
			$RightY1 = $RightY + $Spacing;
			$RightY2 = $RightY + $Spacing + $Value * $YScale;;
			$Settings = [
				"Color" => $this->myPicture->myData->Palette[$Key],
				"NoDraw" => TRUE,
				"Segments" => $Segments,
				"Surrounding" => $Surrounding
			];
			$PolyGon = [];
			$Angle = $this->myPicture->getAngle($X2, $RightY1, $X1, $LeftY1);
			
			$cos = cos(deg2rad($Angle + 90)) * $Force;
			$Offset = ($X2 - $X1) / 2 + $X1;
			$VectorX1 = $cos + $Offset;
			$VectorX2 = -$cos + $Offset;
			
			$sin = sin(deg2rad($Angle + 90)) * $Force;
			$Offset = ($RightY1 - $LeftY1) / 2 + $LeftY1;
			$VectorY1 = $sin + $Offset;
			$VectorY2 = -$sin + $Offset;
			
			$Points = $this->myPicture->drawBezier($X1, $LeftY1, $X2, $RightY1, $VectorX1, $VectorY1, $VectorX2, $VectorY2, $Settings);
			foreach($Points as $Pos) {
				$PolyGon[] = $Pos["X"];
				$PolyGon[] = $Pos["Y"];
			}

			$Angle = $this->myPicture->getAngle($X2, $RightY2, $X1, $LeftY2);
			
			$cos = cos(deg2rad($Angle + 90)) * $Force;
			$Offset = ($X2 - $X1) / 2 + $X1;
			$VectorX1 = $cos + $Offset;
			$VectorX2 = -$cos + $Offset;
			
			$sin = sin(deg2rad($Angle + 90)) * $Force;
			$Offset = ($RightY2 - $LeftY2) / 2 + $LeftY2;
			$VectorY1 = $sin + $Offset;
			$VectorY2 = -$sin + $Offset;
			
			$Points = $this->myPicture->drawBezier($X1, $LeftY2, $X2, $RightY2, $VectorX1, $VectorY1, $VectorX2, $VectorY2, $Settings);
			foreach(array_reverse($Points) as $Pos) {
				$PolyGon[] = $Pos["X"];
				$PolyGon[] = $Pos["Y"];
			}

			$this->myPicture->drawPolygon($PolyGon, $Settings);
			if ($TextPos == TEXT_POS_RIGHT) {
				$this->myPicture->drawText($X2 + $TextPadding, ($RightY2 - $RightY1) / 2 + $RightY1, $Label, ["Align" => TEXT_ALIGN_MIDDLELEFT]);
			} else {
				$this->myPicture->drawText($X2, $RightY1 - $TextPadding, $Label, ["Align" => TEXT_ALIGN_BOTTOMRIGHT]);
			}

			$LeftY = $LeftY2;
			$RightY = $RightY2;
		}
	}
	
	/* Draw the derivative chart associated to the data series */
	function drawDerivative(array $Format = [])
	{
		$Offset = isset($Format["Offset"]) ? $Format["Offset"] : 10;
		$SerieSpacing = isset($Format["SerieSpacing"]) ? $Format["SerieSpacing"] : 3;
		$DerivativeHeight = isset($Format["DerivativeHeight"]) ? $Format["DerivativeHeight"] : 4;
		$ShadedSlopeBox = isset($Format["ShadedSlopeBox"]) ? $Format["ShadedSlopeBox"] : FALSE;
		$DrawBackground = isset($Format["DrawBackground"]) ? $Format["DrawBackground"] : TRUE;
		$BackgroundColor = isset($Format["BackgroundColor"]) ? $Format["BackgroundColor"] : new pColor(255,255,255,20);
		$DrawBorder = isset($Format["DrawBorder"]) ? $Format["DrawBorder"] : TRUE;
		$BorderColor = isset($Format["BorderColor"]) ? $Format["BorderColor"] : new pColor(0);
		$Caption = isset($Format["Caption"]) ? $Format["Caption"] : TRUE;
		$CaptionHeight = isset($Format["CaptionHeight"]) ? $Format["CaptionHeight"] : 10;
		$CaptionWidth = isset($Format["CaptionWidth"]) ? $Format["CaptionWidth"] : 20;
		$CaptionMargin = isset($Format["CaptionMargin"]) ? $Format["CaptionMargin"] : 4;
		$CaptionLine = isset($Format["CaptionLine"]) ? $Format["CaptionLine"] : FALSE;
		$CaptionBox = isset($Format["CaptionBox"]) ? $Format["CaptionBox"] : FALSE;
		$CaptionBorderColor = isset($Format["CaptionBorderColor"]) ? $Format["CaptionBorderColor"] : new pColor(0,0,0,80);
		$CaptionFillColor = isset($Format["CaptionFillColor"]) ? $Format["CaptionFillColor"] : new pColor(255,255,255,80);
		$PositiveSlopeStartColor = isset($Format["PositiveSlopeStartColor"]) ? $Format["PositiveSlopeStartColor"] : new pColor(184,234,88);
		$PositiveSlopeEndColor = isset($Format["PositiveSlopeEndColor"]) ? $Format["PositiveSlopeEndColor"] : new pColor(239,31,36);
		$NegativeSlopeStartColor = isset($Format["NegativeSlopeStartColor"]) ? $Format["NegativeSlopeStartColor"] : new pColor(184,234,88);
		$NegativeSlopeEndColor = isset($Format["NegativeSlopeEndColor"]) ? $Format["NegativeSlopeEndColor"] : new pColor(67,124,227);

		$Data = $this->myPicture->myData->Data;
		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {
			$YPos = $this->myPicture->GraphAreaY2 + $Offset;
		} else {
			$XPos = $this->myPicture->GraphAreaX2 + $Offset;
		}
		/* Momchil: This tweak is a result of poorly re-factored drawScale on my part */
		/* Removal of $this->DataSet->Data["GraphArea"] to be specific */
		$YPos += $this->myPicture->FontSize + 2; 

		foreach($Data["Series"] as $SerieName => $Serie) {
			if ($Serie["isDrawable"] && $SerieName != $Data["Abscissa"]) {

				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				$XStep = $this->getXStep($Data["Orientation"], $XDivs, $XMargin);
				$CaptionSettings = ["Color" => $Serie["Color"],"Ticks" => $Serie["Ticks"],"Weight" => $Serie["Weight"]];
				$LastX = VOID;
				$LastY = VOID;
				$MinSlope = 0;
				$MaxSlope = 1;
				$LastColor = NULL;

				if ($Data["Orientation"] == SCALE_POS_LEFTRIGHT) {
					if ($Caption) {
						if ($CaptionLine) {
							$StartX = floor($this->myPicture->GraphAreaX1 - $CaptionWidth + $XMargin - $CaptionMargin);
							$EndX = floor($this->myPicture->GraphAreaX1 - $CaptionMargin + $XMargin);
							
							if ($CaptionBox) {
								$this->myPicture->drawFilledRectangle($StartX, $YPos, $EndX, $YPos + $CaptionHeight, ["Color" => $CaptionFillColor,"BorderColor" => $CaptionBorderColor]);
							}

							$this->myPicture->drawLine($StartX + 2, $YPos + ($CaptionHeight / 2), $EndX - 2, $YPos + ($CaptionHeight / 2), $CaptionSettings);
						
						} else {
							$this->myPicture->drawFilledRectangle($this->myPicture->GraphAreaX1 - $CaptionWidth + $XMargin - $CaptionMargin, $YPos, $this->myPicture->GraphAreaX1 - $CaptionMargin + $XMargin, $YPos + $CaptionHeight, ["Color" => $Serie["Color"],"BorderColor" => $CaptionBorderColor]);
						}
					}

					$X = $this->myPicture->GraphAreaX1 + $XMargin;
					$TopY = $YPos + ($CaptionHeight / 2) - ($DerivativeHeight / 2);
					$BottomY = $YPos + ($CaptionHeight / 2) + ($DerivativeHeight / 2);
					$StartX = floor($this->myPicture->GraphAreaX1 + $XMargin);
					$EndX = floor($this->myPicture->GraphAreaX2 - $XMargin);
					($DrawBackground) AND $this->myPicture->drawFilledRectangle($StartX - 1, $TopY - 1, $EndX + 1, $BottomY + 1, ["Color" => $BackgroundColor]);
					($DrawBorder) AND $this->myPicture->drawRectangle($StartX - 1, $TopY - 1, $EndX + 1, $BottomY + 1, ["Color" => $BorderColor]);
					$RestoreShadow = $this->myPicture->Shadow;
					$this->myPicture->Shadow = FALSE;
					
					/* Determine the Max slope index */
					foreach($PosArray as $Y) {
						if ($Y != VOID && ($LastX != VOID)) {
							$Slope = ($LastY - $Y);
							($Slope > $MaxSlope) AND $MaxSlope = $Slope;
							($Slope < $MinSlope) AND $MinSlope = $Slope;
						}

						if ($Y == VOID) {
							$LastX = VOID;
							$LastY = VOID;
						} else {
							$LastX = $X;
							$LastY = $Y;
						}
					}

					$LastX = VOID;
					$LastY = VOID;

					foreach($PosArray as $Y) {
						if ($Y != VOID && ($LastY != VOID)) {
							$Slope = $LastY - $Y;
							if ($Slope >= 0) {
								$Gradient = new pColorGradient($PositiveSlopeStartColor, $PositiveSlopeEndColor);
								$SlopeIndex = (100 / $MaxSlope) * $Slope;
							} else {
								$Gradient = new pColorGradient($NegativeSlopeStartColor, $NegativeSlopeEndColor);
								$SlopeIndex = (100 / abs($MinSlope)) * abs($Slope);
							}
							
							$Gradient->SetSegments(100);
							$Color = $Gradient->Next($SlopeIndex, TRUE);
							
							if ($ShadedSlopeBox && !is_null($LastColor)) // && $Slope != 0
							{
								$this->myPicture->drawGradientArea($LastX, $TopY, $X, $BottomY, DIRECTION_HORIZONTAL, ["StartColor"=>$LastColor,"EndColor"=>$Color]);
							} else { # || $Slope == 0
								$this->myPicture->drawFilledRectangle(floor($LastX), $TopY, floor($X), $BottomY, ["Color" => $Color]);
							}
							$LastColor = $Color;
						}

						if ($Y == VOID) {
							$LastY = VOID;
						} else {
							$LastX = $X;
							$LastY = $Y;
						}

						$X += $XStep;
					}

					$YPos = $YPos + $CaptionHeight + $SerieSpacing;
					
				} elseif ($Data["Orientation"] == SCALE_POS_LEFTRIGHT){
				
					if ($Caption) {
						$StartY = floor($this->myPicture->GraphAreaY1 - $CaptionWidth + $XMargin - $CaptionMargin);
						$EndY = floor($this->myPicture->GraphAreaY1 - $CaptionMargin + $XMargin);
						if ($CaptionLine) {
							if ($CaptionBox) {
								$this->myPicture->drawFilledRectangle($XPos, $StartY, $XPos + $CaptionHeight, $EndY, ["Color" => $CaptionFillColor,"BorderColor" => $CaptionBorderColor]);
							}

							$this->myPicture->drawLine($XPos + ($CaptionHeight / 2), $StartY + 2, $XPos + ($CaptionHeight / 2), $EndY - 2, $CaptionSettings);
						} else {
							$this->myPicture->drawFilledRectangle($XPos, $StartY, $XPos + $CaptionHeight, $EndY, ["Color" => $Serie["Color"],"BorderColor" => $CaptionBorderColor]);
						}
					}

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;
					$TopX = $XPos + ($CaptionHeight / 2) - ($DerivativeHeight / 2);
					$BottomX = $XPos + ($CaptionHeight / 2) + ($DerivativeHeight / 2);
					$StartY = floor($this->myPicture->GraphAreaY1 + $XMargin);
					$EndY = floor($this->myPicture->GraphAreaY2 - $XMargin);
					($DrawBackground) AND $this->myPicture->drawFilledRectangle($TopX - 1, $StartY - 1, $BottomX + 1, $EndY + 1, ["Color" => $BackgroundColor]);
					($DrawBorder) AND $this->myPicture->drawRectangle($TopX - 1, $StartY - 1, $BottomX + 1, $EndY + 1, ["Color" => $BorderColor]);
					$RestoreShadow = $this->myPicture->Shadow;
					$this->myPicture->Shadow = FALSE;
					
					/* Determine the Max slope index */
					foreach($PosArray as $X) {
						if ($X != VOID && ($LastX != VOID)) {
							$Slope = ($X - $LastX);
							($Slope > $MaxSlope) AND $MaxSlope = $Slope;
							($Slope < $MinSlope) AND $MinSlope = $Slope;
						}

						$LastX = $X;
					}

					$LastX = VOID;
					$LastY = VOID;

					foreach($PosArray as $X) {
						if ($X != VOID && ($LastX != VOID)) {
							$Slope = $X - $LastX;
							if ($Slope >= 0) {
								$Gradient = new pColorGradient($PositiveSlopeStartColor, $PositiveSlopeEndColor);
								$SlopeIndex = (100 / $MaxSlope) * $Slope;
							} else {
								$Gradient = new pColorGradient($NegativeSlopeStartColor, $NegativeSlopeEndColor);
								$SlopeIndex = (100 / abs($MinSlope)) * abs($Slope);
							}
							
							$Gradient->SetSegments(100);
							$Color = $Gradient->Next($SlopeIndex, TRUE);
							
							if ($ShadedSlopeBox && !is_null($LastColor)) {
								$this->myPicture->drawGradientArea($TopX, $LastY, $BottomX, $Y, DIRECTION_VERTICAL, ["StartColor" => $LastColor,"EndColor" => $Color]);
							} else {
								$this->myPicture->drawFilledRectangle($TopX, floor($LastY), $BottomX, floor($Y), ["Color" => $Color]);
							}

							$LastColor = $Color;
						}

						if ($X == VOID) {
							$LastX = VOID;
						} else {
							$LastX = $X;
							$LastY = $Y;
						}

						$Y += $XStep;
					}

					$XPos = $XPos + $CaptionHeight + $SerieSpacing;
				}

				$this->myPicture->Shadow = $RestoreShadow;
			}
		}
	}
	
	/* Draw the line of best fit */
	function drawBestFit(array $Format = [])
	{
		$OverrideTicks = isset($Format["Ticks"]) ? $Format["Ticks"] : NULL;
		$OverrideColor = isset($Format["OverrideColor"]) ? $Format["OverrideColor"] : NULL;

		list($XMargin, $XDivs) = $this->myPicture->myData->scaleGetXSettings();
		
		$XStep = $this->getXstep($this->myPicture->myData->Data["Orientation"], $XDivs, $XMargin);

		foreach($this->myPicture->myData->Data["Series"] as $SerieName => $Serie) {
			
			if ($Serie["isDrawable"] && $SerieName != $this->myPicture->myData->Data["Abscissa"]) {
				
				$Settings = (!is_null($OverrideColor)) ? ["Color" => $OverrideColor] : ["Color" => $Serie["Color"]];
				$Settings["Ticks"] = (is_null($OverrideTicks)) ? $Serie["Ticks"] : $OverrideTicks;

				$PosArray = $this->myPicture->scaleComputeY($Serie["Data"], $Serie["Axis"]);
				$n = count(array_diff($PosArray, [VOID])); //$n = count($PosArray);
				
				$Sxy = 0;
				$Sx = 0;
				$Sy = 0;
				$Sxx = 0;

				if ($this->myPicture->myData->Data["Orientation"] == SCALE_POS_LEFTRIGHT) {

					$X = $this->myPicture->GraphAreaX1 + $XMargin;

					foreach($PosArray as $Y) {
						if ($Y != VOID) {
							$Sxy += $X * $Y;
							$Sx  += $X;
							$Sy  += $Y;
							$Sxx += $X * $X;
						}

						$X += $XStep;
					}

					$M = (($n * $Sxy) - ($Sx * $Sy)) / (($n * $Sxx) - ($Sx * $Sx));
					$B = (($Sy) - ($M * $Sx)) / ($n);
					$X1 = $this->myPicture->GraphAreaX1 + $XMargin;
					$Y1 = $M * $X1 + $B;
					$X2 = $this->myPicture->GraphAreaX2 - $XMargin;
					$Y2 = $M * $X2 + $B;
					if ($Y1 < $this->myPicture->GraphAreaY1) {
						$X1 = $X1 + ($this->myPicture->GraphAreaY1 - $Y1);
						$Y1 = $this->myPicture->GraphAreaY1;
					}

					if ($Y1 > $this->myPicture->GraphAreaY2) {
						$X1 = $X1 + ($Y1 - $this->myPicture->GraphAreaY2);
						$Y1 = $this->myPicture->GraphAreaY2;
					}

					if ($Y2 < $this->myPicture->GraphAreaY1) {
						$X2 = $X2 - ($this->myPicture->GraphAreaY1 - $Y2);
						$Y2 = $this->myPicture->GraphAreaY1;
					}

					if ($Y2 > $this->myPicture->GraphAreaY2) {
						$X2 = $X2 - ($Y2 - $this->myPicture->GraphAreaY2);
						$Y2 = $this->myPicture->GraphAreaY2;
					}

					$this->myPicture->drawLine($X1, $Y1, $X2, $Y2, $Settings);
					
				} else {

					$Y = $this->myPicture->GraphAreaY1 + $XMargin;

					foreach($PosArray as $X) {
						if ($X != VOID) {
							$Sxy = $Sxy + $X * $Y;
							$Sx = $Sx + $Y;
							$Sy = $Sy + $X;
							$Sxx = $Sxx + $Y * $Y;
						}

						$Y += $XStep;
					}

					$M = (($n * $Sxy) - ($Sx * $Sy)) / (($n * $Sxx) - ($Sx * $Sx));
					$B = (($Sy) - ($M * $Sx)) / $n;
					$Y1 = $this->myPicture->GraphAreaY1 + $XMargin;
					$X1 = $M * $Y1 + $B;
					$Y2 = $this->myPicture->GraphAreaY2 - $XMargin;
					$X2 = $M * $Y2 + $B;
					if ($X1 < $this->myPicture->GraphAreaX1) {
						$Y1 = $Y1 + ($this->myPicture->GraphAreaX1 - $X1);
						$X1 = $this->myPicture->GraphAreaX1;
					}

					if ($X1 > $this->myPicture->GraphAreaX2) {
						$Y1 = $Y1 + ($X1 - $this->myPicture->GraphAreaX2);
						$X1 = $this->myPicture->GraphAreaX2;
					}

					if ($X2 < $this->myPicture->GraphAreaX1) {
						$Y2 = $Y2 - ($this->myPicture->GraphAreaY1 - $X2);
						$X2 = $this->myPicture->GraphAreaX1;
					}

					if ($X2 > $this->myPicture->GraphAreaX2) {
						$Y2 = $Y2 - ($X2 - $this->myPicture->GraphAreaX2);
						$X2 = $this->myPicture->GraphAreaX2;
					}

					$this->myPicture->drawLine($X1, $Y1, $X2, $Y2, $Settings);
				}
			}
		}
	}

}

?>