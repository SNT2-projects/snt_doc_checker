<?php


if (!defined('_PS_VERSION_')) {
    exit;
}


class S3Utils
{
    private $s3Key;
    private $s3Secret;
    private $s3Bucket;
    private $s3StorageType;
    private $awsRegion;

    public function __construct()
    {
        $this->s3Key = Configuration::get('SNT_DOC_CHECKER_S3_KEY');
        $this->s3Secret = Configuration::get('SNT_DOC_CHECKER_S3_SECRET');
        $this->s3Bucket = Configuration::get('SNT_DOC_CHECKER_S3_BUCKET');
        $this->s3StorageType = Configuration::get('SNT_DOC_CHECKER_S3_STORAGE_TYPE');
        $this->awsRegion = Configuration::get('SNT_DOC_CHECKER_AWS_REGION');
    }

    public function putS3($filePath) 
    {
        $date = gmdate('D, d M Y H:i:s T');
        $acl = 'x-amz-acl:private';
        $contentType = 'application/x-compressed-tar';
        $storageType = 'x-amz-storage-class:' . $this->s3StorageType;
        $fileName = basename($filePath);
        
        $stringToSign = "PUT\n\n{$contentType}\n{$date}\n{$acl}\n{$storageType}\n/{$this->s3Bucket}/{$fileName}";
        
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->s3Secret, true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$this->s3Bucket}.s3.{$this->awsRegion}.scw.cloud/{$fileName}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PUT, 1);
        curl_setopt($ch, CURLOPT_INFILE, fopen($filePath, 'r'));
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filePath));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Host: {$this->s3Bucket}.s3.{$this->awsRegion}.scw.cloud",
            "Date: {$date}",
            "Content-Type: {$contentType}",
            $storageType,
            $acl,
            "Authorization: AWS {$this->s3Key}:{$signature}"
        ));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    public function getS3($fileName) 
    {
        $date = gmdate('D, d M Y H:i:s T');
        
        $stringToSign = "GET\n\n\n{$date}\n/{$this->s3Bucket}/{$fileName}";
        
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->s3Secret, true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$this->s3Bucket}.s3.{$this->awsRegion}.scw.cloud/{$fileName}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Host: {$this->s3Bucket}.s3.{$this->awsRegion}.scw.cloud",
            "Date: {$date}",
            "Authorization: AWS {$this->s3Key}:{$signature}"
        ));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $result;
        } else {
            return false;
        }
    }
    public function listS3($maxKeys = 1000, $marker = '') 
    {
        $date = gmdate('D, d M Y H:i:s T');
        
        // Si maxKeys est 0, on utilise 1000 pour les requêtes individuelles mais on récupère tout
        $requestMaxKeys = ($maxKeys == 0) ? 1000 : $maxKeys;
        
        $queryParams = "?max-keys=" . $requestMaxKeys;
        if (!empty($marker)) {
            $queryParams .= "&marker=" . urlencode($marker);
        }
        
        $stringToSign = "GET\n\n\n{$date}\n/{$this->s3Bucket}/";
        
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->s3Secret, true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$this->s3Bucket}.s3.{$this->awsRegion}.scw.cloud/" . $queryParams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Host: {$this->s3Bucket}.s3.{$this->awsRegion}.scw.cloud",
            "Date: {$date}",
            "Authorization: AWS {$this->s3Key}:{$signature}"
        ));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $xml = simplexml_load_string($result);
            $json = json_encode($xml);
            $response = json_decode($json, true);
            
            // Si on veut tous les résultats et qu'il y en a d'autres, on continue de paginer
            if ($maxKeys == 0 && isset($response['IsTruncated']) && $response['IsTruncated'] == 'true') {
                $allContents = isset($response['Contents']) ? $response['Contents'] : array();
                
                // Gérer le cas où Contents peut être un objet simple (1 seul fichier) au lieu d'un tableau
                if (!is_array($allContents) || isset($allContents['Key'])) {
                    $allContents = array($allContents);
                }
                
                $nextMarker = '';
                
                // Boucle pour récupérer toutes les pages
                while (isset($response['IsTruncated']) && $response['IsTruncated'] == 'true') {
                    // Utiliser le dernier fichier comme marker pour la page suivante
                    if (isset($response['Contents'])) {
                        $contents = $response['Contents'];
                        if (!is_array($contents) || isset($contents['Key'])) {
                            $contents = array($contents);
                        }
                        $nextMarker = $contents[count($contents) - 1]['Key'];
                    }
                    
                    // Récupérer la page suivante
                    $nextResponse = $this->listS3($requestMaxKeys, $nextMarker);
                    
                    if ($nextResponse && isset($nextResponse['Contents'])) {
                        $nextContents = $nextResponse['Contents'];
                        
                        // Gérer le cas où Contents peut être un objet simple
                        if (!is_array($nextContents) || isset($nextContents['Key'])) {
                            $nextContents = array($nextContents);
                        }
                        
                        $allContents = array_merge($allContents, $nextContents);
                        $response = $nextResponse;
                    } else {
                        break;
                    }
                }
                
                $response['Contents'] = $allContents;
                $response['IsTruncated'] = 'false';
            }
            
            return $response;
        } else {
            return false;
        }
    }

    public function getS3PresignedUrl($fileName, $expirationSeconds = 3600, $extension)
    {
        $expires = time() + $expirationSeconds;
    
        // Ajout des headers de réponse
        $disposition = 'inline';
        $contentType = 'application/' . $extension;
    
        $queryParams = "response-content-disposition={$disposition}&response-content-type={$contentType}";
    
        $stringToSign = "GET\n\n\n{$expires}\n/{$this->s3Bucket}/{$fileName}?{$queryParams}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->s3Secret, true));
        $signatureEncoded = urlencode($signature);
    
        return "https://{$this->s3Bucket}.s3.{$this->awsRegion}.scw.cloud/{$fileName}"
             . "?{$queryParams}"
             . "&AWSAccessKeyId=" . urlencode($this->s3Key)
             . "&Expires=" . $expires
             . "&Signature=" . $signatureEncoded;
    }
    


    public function deleteS3($fileName) 
    {
        $date = gmdate('D, d M Y H:i:s T');
        $stringToSign = "DELETE\n\n\n{$date}\n/{$this->s3Bucket}/{$fileName}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->s3Secret, true));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://{$this->s3Bucket}.s3.{$this->awsRegion}.scw.cloud/{$fileName}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Host: {$this->s3Bucket}.s3.{$this->awsRegion}.scw.cloud",
            "Date: {$date}",
            "Authorization: AWS {$this->s3Key}:{$signature}"
        ));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode == 204 || $httpCode == 200) ? 1 : 0;
    }

    public function deleteMultipleS3($files) 
    {
        $results = array();
        
        foreach ($files as $fileName) {
            $result = $this->deleteS3($fileName);
            $results[] = array(
                'file' => $fileName,
                'deleted' => $result
            );
        }
        
        return $results;
    }

    /**
     * Récupère TOUS les fichiers du bucket S3 sans limitation
     * @return array|false
     */
    public function listAllS3Files()
    {
        return $this->listS3(0); // 0 = récupérer tous les fichiers
    }

    public function countS3Files()
    {
        // Utiliser listAllS3Files() pour compter tous les fichiers
        $listResult = $this->listAllS3Files();
        
        if ($listResult === false) {
            return 0;
        }

        if (isset($listResult['Contents'])) {
            if (is_array($listResult['Contents'])) {
                // Vérifier si c'est un tableau de fichiers ou un seul fichier
                if (isset($listResult['Contents']['Key'])) {
                    // Un seul fichier
                    return 1;
                } else {
                    // Tableau de fichiers
                    return count($listResult['Contents']);
                }
            }
            // Si un seul fichier, Contents ne sera pas un tableau
            return 1;
        }

        return 0;
    }

    /**
     * Génère un lien temporaire pour consulter un fichier S3 qui expire au bout de 60 minutes
     * @param string $fileName Nom du fichier dans le bucket S3
     * @return string|false URL temporaire du fichier ou false en cas d'erreur
     */
    public function getTemporaryConsultationUrl($fileName, $extension)
    {
        // 60 minutes = 3600 secondes
        $expirationSeconds = 3600;
        
        return $this->getS3PresignedUrl($fileName, $expirationSeconds, $extension);
    }

    /**
     * Génère un lien temporaire optimisé pour la prévisualisation PDF
     * @param string $fileName Nom du fichier dans le bucket S3
     * @return string|false URL temporaire du fichier ou false en cas d'erreur
     */

}