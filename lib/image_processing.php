<?php

namespace TAO;

class ImageProcessing
{
	public static function OnBeforeResizeImageHandler($arFile, $arResizeParams, &$callbackData, &$bNeedResize)
	{
		if (self::IsRequiredFix($arResizeParams)) {
			$bNeedResize = false;
			$callbackData['newSize'] = self::CalcImageSize($arFile['WIDTH'], $arFile['HEIGHT'], $arResizeParams[0]['width'], $arResizeParams[0]['height']);
			return true;
		}
	}

	public static function OnAfterResizeImageHandler($arFile, $arResizeParams, &$callbackData, &$cacheImageFile, &$cacheImageFileTmp)
	{
		if (self::IsRequiredFix($arResizeParams) && $callbackData['newSize']) {
			$sourceFile = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($cacheImageFile, '/');
			\CFile::ResizeImageFile($sourceFile, $cacheImageFileTmp, $callbackData['newSize'], $arResizeParams[1], $arResizeParams[2], 95, $arResizeParams[4]);
			$cacheImageFile = str_replace($_SERVER["DOCUMENT_ROOT"], '',  $cacheImageFileTmp);
		}
	}

	protected static function CalcImageSize($srcWidth, $srcHeight, $destWidth, $destHeight)
	{
		if ($srcWidth <= $destWidth && $srcHeight <= $destHeight) {
			$width = $srcWidth;
			$height = $srcHeight;
		} else {
			$srcFactor = $srcWidth / $srcHeight;
			$destFactor = $destWidth / $destHeight;

			if ($srcFactor >= $destFactor) {
				$width = $destWidth;
				$height = $width / $destFactor;
				if ($height > $srcHeight) {
					$height = $srcHeight;
				}
			} else {
				$height = $destHeight;
				$width = $destHeight * $destFactor;
				if ($width > $srcWidth) {
					$width = $srcWidth;
				}
			}
		}
		return array('width' => $width, 'height' => $height);
	}

	protected static function IsRequiredFix($arResizeParams) {
		return $arResizeParams[1] == BX_RESIZE_IMAGE_EXACT;
	}
}
