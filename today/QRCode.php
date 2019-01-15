<?php

/**
* QR Code
*/

require_once 'qrcode/phpqrcode.php';

class MyQRCode {

	public function createQRCode( $text, $outfile = false, $level = 'L', $size = 3, $margin = 4 ) {
		return QRcode::png( $text, $outfile, $level, $size, $margin );
	}

	public function getQRCodeBase64( $text, $outfile = false, $level = 'L', $size = 3, $margin = 4 ) {
		/* 示例
		require_once 'core/v2.1/QRCode.php';
		$qr = new MyQRCode();
		$base64 = $qr->getQRCodeBase64( HTTP_ROOT.'check/'.$order['id'], 'data/'.$this->createRnd().'.png', 'L', 5 );
		$this->setData( $base64, 'base64' );
		*/

		//防止恶意替换文件并最终删除该文件
		if ( file_exists( $outfile ) ) return;

		$this->createQRCode( $text, $outfile, $level, $size, $margin );
		$fp = fopen( $outfile, 'r' );
		$file = fread( $fp, filesize( $outfile ) );
		fclose( $fp );
		unlink( $outfile );
		return 'data:image/png;base64,'.chunk_split( base64_encode( $file ) );
	}

	public function getQRCodeImage( $text, $outfile = false, $level = 'L', $size = 3, $margin = 4 ) {
		/* 示例
		$code = trim( get2( 'code' ) );
		if ( empty( $code ) ) exit;

		ob_end_clean();
		header( 'content-type:image/png' );

		require_once 'core/v2.1/QRCode.php';

		$qr = new MyQRCode;
		$im = $qr->getQRCodeImage( '<'.$code.'>', 'data/'.$this->createRnd().'.png', 'L', 5 );
		imagepng( $im );
		*/

		//防止恶意替换文件并最终删除该文件
		if ( file_exists( $outfile ) ) return;

		$this->createQRCode( $text, $outfile, $level, $size, $margin );
		$im = imagecreatefrompng( $outfile );
		return $im;
	}

}

?>