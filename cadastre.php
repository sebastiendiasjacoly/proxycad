<?php
// set cadastre.gouv.fr premium key : 
$premium = '**key**';
// set postgis connection
$pg_config = "host=**host** port=**port** dbname=**dbname** user=**user** password=**password**";
//Set max scale to use
$maxscale = 25000;

$_GET_lower = array_change_key_case($_GET, CASE_LOWER);
if (!isset($_GET_lower['request'], $_GET_lower['service'])) {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(405);
        echo "Méthode non autorisée.";
        return;
    }    
    $request = $_GET_lower['request'];
    $data = array();
    foreach ($_GET as $name => $value) {
        //echo $name . ' : ' . $value . '<br />';
        $data[$name] = $value;
    }    
    if (strtolower($request) == 'getcapabilities') {
        header('Content-Type: text/xml');
        $capabilities = file_get_contents('capabilities.xml');
        echo $capabilities;
    }
    if (strtolower($request) == 'getmap') {
        // Check for All mandatory parameters
        if (isset($_GET['BBOX'], $_GET['CRS'], $_GET['WIDTH'], $_GET['HEIGHT'], $_GET['LAYERS'], $_GET['FORMAT'])) {       
            $parameters =  http_build_query( $data );
            $bbox = explode(',', $_GET['BBOX']);  
            $crs = $_GET['CRS'];    
            $epsg = explode(':', $crs)[1];
            $height = intval($_GET['HEIGHT']);
            $width = intval($_GET['WIDTH']);
            $format = $_GET['FORMAT'];            
            $scale = ($bbox[2]-$bbox[0])/($width *0.00028);            
            
            $dbh = pg_connect($pg_config);            
             if (!$dbh) {
                 echo "Connexion à la Base Impossible avec les paramètres fournis";	          
                 die();
             }    
             
             $sql = "SELECT insee
                    FROM osm.communes_france 
                    WHERE ST_Intersects(geom, 
                        ST_Transform(ST_MakeEnvelope($bbox[0],$bbox[1],$bbox[2],$bbox[3],$epsg),2154)) LIMIT 10";             
            
             $raws = pg_query($dbh, $sql);    
             
             if (!$raws) {
                echo  pg_last_error($dbh);        
                 die();
            }
            $nbrows = pg_num_rows($raws);
            $communes = pg_fetch_all($raws);
            pg_close($dbh);
            if ($nbrows == 1) {
                $insee = $communes[0]['insee'];                
                $service = 'http://inspire.cadastre.gouv.fr/scpc/'. $premium .'/'. $insee .'.wms?';
                header("location:" .$service . $parameters, TRUE, 302);
                exit;
            } else {
                if ($scale <= $maxscale) {
                    //Proxy
                     $aContext = array(
                        'http' => array(
                            'proxy' => 'tcp://192.168.1.73:8080',
                            'request_fulluri' => true,
                        ),
                    );
                    $cxContext = stream_context_create($aContext);    
                    $nbtiles = 0;    
                    foreach ($communes as $commune) {
                        $insee = ($commune['insee']);
                        $service = 'http://inspire.cadastre.gouv.fr/scpc/'. $premium .'/'. $insee .'.wms?';
                        if ($nbtiles == 0) {            
                            $img = imagecreatefromstring(file_get_contents($service . $parameters, False, $cxContext));
                            imagealphablending($img, true);
                            imagesavealpha($img, true);
                        } else {
                            $frame = imagecreatefromstring(file_get_contents($service . $parameters, False, $cxContext));
                            imagealphablending($frame, true);
                            imagesavealpha($frame, true);
                            imagecopy($img, $frame, 0, 0, 0, 0, $height, $width);
                            imagedestroy($frame);
                        }
                        $nbtiles++;
                    } 
                    
                    if ($img !== false) {
                        header('mergedTiles:' .$nbtiles);
                        header('scale:' .$scale);
                        switch ($format) {
                            case "image/png":
                                header('Content-Type: image/png');
                                imagepng($img);
                                break;
                            case "image/jpeg":
                                header('Content-Type: image/jpeg');
                                imagejpeg($img);
                                break;
                            case "image/gif":
                                header('Content-Type: image/gif');
                                imagegif($img);
                                break;
                            default:
                                header('Content-Type: text/html; charset=utf-8');
                                http_response_code(400);
                                echo "Format d'image non pris en compte.";
                        }                
                        imagedestroy($img);
                    }
                    else {    
                        header('Content-Type: text/html; charset=utf-8');
                        http_response_code(400);
                        echo 'Erreur GetMap';
                    }
                } else {
                    header('Content-Type: text/html; charset=utf-8');
                    header('scale:' .$scale);
                    http_response_code(400);
                    echo 'Erreur GetMap : Echelle non autorisée.';
                }
            }
        }  else { 
            header('Content-Type: text/html; charset=utf-8');
            http_response_code(400);
            echo 'Erreur GetMap : Requête mal formée.';
        }        
    } else {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(405);        
        return;
    }
    ?>   