<?php
require_once __DIR__ . '/../includes/db.php';

class VisionProcessor {
    private $pdo;
    private $api_keys;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->api_keys = API_KEYS;
        
        if (empty($this->api_keys)) {
            throw new Exception("No Gemini API keys found in configuration. Please check your Environment Variables.");
        }
    }

    private function getNextApiKey() {
        static $index = 0;
        $key = $this->api_keys[$index % count($this->api_keys)];
        $index++;
        return $key;
    }

    public function processImage($imagePath, $prompt) {
        $apiKey = $this->getNextApiKey();
        
        // Use standard model identifiers
        $models = [
            'gemini-1.5-flash',
            'gemini-1.5-flash-latest',
            'gemini-pro-vision'
        ];

        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);
        $errors = [];

        foreach ($models as $modelName) {
            // Try both v1 and v1beta for each model
            $versions = ['v1beta', 'v1'];
            
            foreach ($versions as $version) {
                $fullModelName = (strpos($modelName, 'models/') === 0) ? $modelName : 'models/' . $modelName;
                $apiUrl = "https://generativelanguage.googleapis.com/{$version}/{$fullModelName}:generateContent?key=" . $apiKey;

                $payload = [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $prompt],
                                [
                                    "inline_data" => [
                                        "mime_type" => $mimeType,
                                        "data" => $imageData
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    $result = json_decode($response, true);
                    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                        $text = trim($result['candidates'][0]['content']['parts'][0]['text']);
                        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
                            $text = $matches[0];
                        }
                        $data = json_decode($text, true);
                        if (is_array($data)) {
                            if (isset($data[0])) $data = $data[0];
                            // Save working model to log for debugging
                            file_put_contents(TEMP_FILE_PATH . 'working_model.txt', "$version / $modelName");
                            return $data;
                        }
                    }
                } else {
                    $errorData = json_decode($response, true);
                    $apiErrorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : '';
                    $errors[] = "[$version / $modelName]: HTTP $httpCode" . ($apiErrorMsg ? " - $apiErrorMsg" : "");
                }
            }
        }

        throw new Exception("All models failed. Details:\n" . implode("\n", $errors));
    }

    public function convertPdfToJpg($sourcePath) {
        $mimeType = mime_content_type($sourcePath);
        if ($mimeType !== 'application/pdf' || !class_exists('Imagick')) return $sourcePath;
        try {
            $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
            $targetPath = UPLOAD_DIR . $filename . '_preview.jpg';
            $im = new Imagick();
            $im->setResolution(150, 150); 
            $im->readImage($sourcePath . '[0]');
            $im->setImageFormat('jpg');
            $im->writeImage($targetPath);
            $im->clear(); $im->destroy();
            return $targetPath;
        } catch (Exception $e) {}
        return $sourcePath;
    }

    public function splitImage($sourcePath) {
        $sourcePath = $this->convertPdfToJpg($sourcePath);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $imgInfo = getimagesize($sourcePath);
        if (!$imgInfo) return false;
        $width = $imgInfo[0]; $height = $imgInfo[1];
        $topPath = SPLIT_DIR . $filename . '_top.jpg';
        $bottomPath = SPLIT_DIR . $filename . '_bottom.jpg';
        if (class_exists('Imagick')) {
            $im = new Imagick($sourcePath);
            $top = clone $im; $top->cropImage($width, $height / 2, 0, 0); $top->writeImage($topPath);
            $bottom = clone $im; $bottom->cropImage($width, $height / 2, 0, $height / 2); $bottom->writeImage($bottomPath);
            $im->clear(); $im->destroy();
        } else {
            $source = imagecreatefromjpeg($sourcePath);
            $top = imagecreatetruecolor($width, $height / 2);
            imagecopy($top, $source, 0, 0, 0, 0, $width, $height / 2);
            $bottom = imagecreatetruecolor($width, $height / 2);
            imagecopy($bottom, $source, 0, 0, 0, $height / 2, $width, $height / 2);
            imagejpeg($top, $topPath); imagejpeg($bottom, $bottomPath);
            imagedestroy($source); imagedestroy($top); imagedestroy($bottom);
        }
        return ['top' => $topPath, 'bottom' => $bottomPath];
    }
}
?>
