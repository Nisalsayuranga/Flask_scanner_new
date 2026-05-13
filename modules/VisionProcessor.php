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
        $workingModelFile = TEMP_FILE_PATH . 'working_model.txt';
        
        // 1. Try to load cached working model first
        $cachedModel = null;
        if (file_exists($workingModelFile)) {
            $cachedModel = trim(file_get_contents($workingModelFile));
        }

        // 2. Prepare discovery list based on what we found is available to your key
        $models = [
            'gemini-2.0-flash',
            'gemini-flash-latest',
            'gemini-2.0-flash-lite',
            'gemini-pro-latest'
        ];
        $versions = ['v1', 'v1beta'];

        // 3. Prioritize cached model if available
        if ($cachedModel) {
            list($cachedVersion, $cachedName) = explode('|', $cachedModel);
            // Move this to the front of our search
            array_unshift($models, $cachedName);
            $models = array_unique($models);
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);
        $errors = [];

        foreach ($models as $modelName) {
            foreach ($versions as $version) {
                // If we have a cache and it's not THIS combination, we might want to skip it in discovery
                // but for robustness we'll just iterate.
                
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
                            
                            // Save this successful combination as the working model
                            file_put_contents($workingModelFile, "$version|$modelName");
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

        // If all failed, try to list what models are actually available to this key
        $listUrl = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;
        $ch = curl_init($listUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $listResponse = curl_exec($ch);
        curl_close($ch);
        
        $availableModels = "Could not list models.";
        $listData = json_decode($listResponse, true);
        if (isset($listData['models'])) {
            $names = array_map(function($m) { return $m['name']; }, $listData['models']);
            $availableModels = implode(", ", $names);
        }

        throw new Exception("All models failed. \nAvailable to this key: $availableModels\n\nDetails:\n" . implode("\n", $errors));
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
