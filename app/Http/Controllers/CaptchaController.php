<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CaptchaController extends Controller
{
    /**
     * Generate and display CAPTCHA image
     */
    public function generate()
    {
        // Generate random code (5-7 characters)
        $code = $this->generateRandomCode();
        
        // Store in session
        Session::put('captcha_code', strtolower($code));
        
        // Create image
        $width = 120;
        $height = 40;
        
        try {
            $manager = new ImageManager(new Driver());
            $img = $manager->create($width, $height);
            
            // Set background color (light gray)
            $img->fill(240, 240, 240);
            
            // Add text with random colors and positions
            $x = 10;
            $y = 25;
            $fontSize = 20;
            
            for ($i = 0; $i < strlen($code); $i++) {
                $char = $code[$i];
                $color = $this->getRandomColor();
                
                // Random rotation
                $angle = rand(-15, 15);
                
                // Add character
                $img->text($char, $x + ($i * 18), $y, function ($font) use ($fontSize, $color, $angle) {
                    $font->file(public_path('fonts/arial.ttf'))->size($fontSize)->color($color[0], $color[1], $color[2]);
                });
                
                // Add some noise lines
                if (rand(0, 1)) {
                    $img->line(rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), function ($draw) {
                        $color = $this->getRandomColor();
                        $draw->color($color[0], $color[1], $color[2]);
                    });
                }
            }
            
            // Return image
            return response($img->toPng(), 200)
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
                
        } catch (\Exception $e) {
            // Fallback: simple text-based CAPTCHA
            return response($this->generateSimpleCaptcha($code), 200)
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }
    }
    
    /**
     * Generate random CAPTCHA code
     */
    private function generateRandomCode($length = 6)
    {
        $characters = 'abcdefghjkmnpqrstuvwxyz23456789'; // Exclude confusing characters
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code;
    }
    
    /**
     * Get random color
     */
    private function getRandomColor()
    {
        return [
            rand(50, 150), // R
            rand(50, 150), // G
            rand(50, 150)  // B
        ];
    }
    
    /**
     * Simple text-based CAPTCHA fallback
     */
    private function generateSimpleCaptcha($code)
    {
        $img = imagecreate(120, 40);
        $bg = imagecolorallocate($img, 240, 240, 240);
        $textColor = imagecolorallocate($img, 50, 50, 50);
        
        imagestring($img, 5, 20, 10, $code, $textColor);
        
        ob_start();
        imagepng($img);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($img);
        
        return $imageData;
    }
}



