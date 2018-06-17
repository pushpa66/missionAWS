<?php
function sign($key, $msg){
    return hash_hmac('sha256', $msg, $key, true);
}

function getSignatureKey($key, $dateStamp, $regionName, $serviceName){
    $kDate = sign("AWS4$key", $dateStamp);
//    echo $kDate."\n";
    $kRegion = sign($kDate, $regionName);
//    echo $kRegion."\n";
    $kService = sign($kRegion, $serviceName);
//    echo $kService."\n";
    $kSigning = sign($kService, 'aws4_request');
//    echo $kSigning."\n";
    return $kSigning;
}

function getData($id){

    $results = array();

    $method = 'GET';
    $service = 'execute-api';
    $host = '02a4yfeonl.execute-api.eu-west-2.amazonaws.com';
    $region = 'eu-west-2';
    $endpoint = 'https://02a4yfeonl.execute-api.eu-west-2.amazonaws.com/dev/viewMission';
    $request_parameters = "id=$id";

    $access_key = "";
    $secret_key = "";

    $amzdate = gmdate('Ymd\THis\Z');
    $datestamp = gmdate('Ymd');

    $canonical_uri = '/dev/viewMission';
    $canonical_query_string = $request_parameters;
    $canonical_headers = "host:$host\nx-amz-date:$amzdate\n";
    $signed_headers = 'host;x-amz-date';
    $payload_hash = hash('sha256', "");

    $canonical_request = "$method\n$canonical_uri\n$canonical_query_string\n$canonical_headers\n$signed_headers\n$payload_hash";
    $canonical_request_hash = hash('sha256', $canonical_request);

    $algorithm = 'AWS4-HMAC-SHA256';
    $credential_scope = "$datestamp/$region/$service/aws4_request";

    $string_to_sign = "$algorithm\n$amzdate\n$credential_scope\n$canonical_request_hash";

    $signing_key = getSignatureKey($secret_key, $datestamp, $region, $service);

    $signature = hash_hmac("sha256", $string_to_sign, $signing_key);

//    echo $signature."\n";

    $authorization_header = "$algorithm Credential=$access_key/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";

    $headers = array("x-amz-date: $amzdate", "Authorization: $authorization_header");

//    var_dump($headers);

    $request_url = "$endpoint?$request_parameters";

//    echo "\n".$request_url."\n";

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $request_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => $headers
    ));


    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        $results = json_decode($response, true);
//        echo $results;
    }
    return $results;
}

$data = getData(1);

$associatedProductsCount = count($data['associatedProducts']);
$associatedProducts = array();

if ($associatedProductsCount > 10){
    for ($i = 0; $i < 10 ; $i++){
        array_push($associatedProducts, $data['associatedProducts'][$i]);
    }
} else {
    $associatedProducts = $data['associatedProducts'];
}

$similarMissionsCount = count($data['similarMissions']);
$similarMissions = array();

if ($similarMissionsCount > 3){
    for ($i = 0; $i < 3 ; $i++){
        array_push($similarMissions, $data['similarMissions'][$i]);
    }
} else {
    $similarMissions = $data['similarMissions'];
}

?>

<!DOCTYPE html>
<html>
<head>
    <!--Import Google Icon Font-->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!--Import materialize.css-->
    <link type="text/css" rel="stylesheet" href="css/materialize.css"  media="screen,projection"/>

    <!--Let browser know website is optimized for mobile-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script src= "https://cdn.zingchart.com/zingchart.min.js"></script>
    <script> zingchart.MODULESDIR = "https://cdn.zingchart.com/modules/"; ZC.LICENSE = ["569d52cefae586f634c54f86dc99e6a9","ee6b7db5b51705a13dc2339db3edaf6d"];</script>
</head>
<body>
<style>
    .scroll-box-1 {
        overflow-y: scroll;
        height: 80vh;
        padding: 1rem
    }
    .scroll-box-2 {
        overflow-y: scroll;
        height: 60vh;
        padding: 1rem
    }
    .gauge{height: 150px;width: 150px;}
    .venn{height: 200px;width: 200px;}
    .boxplot{height: 250px;width:400px;}

</style>


<nav>
    <div class="orange nav-wrapper">
        <a href="#" class="brand-logo left"><img src="img/logo.png" height=45% width=45%/></a>
        <a href="#" class="brand-logo center">Mission: <?php echo $data["missionName"]?></a>
        <ul id="nav-mobile" class="right hide-on-med-and-down">
            <li><a href="index">Home</a></li>
            <li><a href="viewMissions">Missions</a></li>
            <li><a href="viewProducts">Products</a></li>
        </ul>
    </div>
</nav>

<br/>

<div class="row">
    <div class="col s4">

        <!-- ASSOCIATED PRODUCTS START -->

        <div class="card blue-grey lighten-3">
            <div class="card-content white-text scroll-box-1">
                <span class="card-title">Associated Products</span>
                <?php foreach($associatedProducts as $d){ ?>
                    <div class="row">
                        <div class="col s5">
                            <div class="gauge" id="gauge-<?php echo $d["id"] ?>"></div>
                        </div>
                        <div class="col s7"><a href="viewProduct?id=<?php echo $d['id']?>" class="blue-text text-darken-2">Code: <?php echo $d["code"] ?></a><br/><?php echo $d["id"] ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- ASSOCIATED PRODUCTS END -->

    </div>
    <div class="col s8">
        <div class="row">
            <div class="col s12">

                <!-- MISSION STRENGTH START -->

                <div class="card blue-grey lighten-3">
                    <div class="card-content white-text">
                        <span class="card-title">Mission Strength</span>
<!--                        <div class="boxplot" id="myChart"></div>-->
                        <div class="row">
                            <div class="col s8">
                                <div class="boxplot" id="myChart"></div>
                            </div>
                            <div class="col s4">
                                <h4 style="font-size: 20px">Min : <?php echo $data['strengthMin'] * 100 ?></h4>
                                <h4 style="font-size: 20px">Q1  : <?php echo $data['strengthIQLow'] * 100 ?></h4>
                                <h4 style="font-size: 20px">Q2  : <?php echo $data['strengthMed'] * 100 ?></h4>
                                <h4 style="font-size: 20px">Q3  : <?php echo $data['strengthIQHigh'] * 100 ?></h4>
                                <h4 style="font-size: 20px">Max : <?php echo $data['strengthMax'] * 100 ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MISSION STRENGTH END -->


            </div>
        </div>
        <div class="row">
            <div class="col s4">
                <div class="row">
                    <div class="col s12">

                        <!-- CONVERSION RATE START -->

                        <div class="card blue-grey darken-1">
                            <div class="card-content white-text">
                                <span class="card-title">Conversion Rate</span>
                                <h3 class="right-align"><?php echo $data['conversion'] * 100 ?>%</h3>
                            </div>
                        </div>

                        <!-- CONVERSION RATE END -->

                    </div>
                </div>
                <div class="row">
                    <div class="col s12">

                        <!-- TRAFFIC SHARE START -->

                        <div class="card blue-grey darken-1">
                            <div class="card-content white-text">
                                <span class="card-title">Traffic Share</span>
                                <h3 class="right-align"><?php echo $data['trafficShare'] * 100 ?>%</h3>
                            </div>
                        </div>

                        <!-- TRAFFIC SHARE END -->

                    </div>
                </div>
            </div>
            <div class="col s8">

                <!-- SIMILAR MISSIONS START -->

                <div class="card blue-grey lighten-3 scroll-box-2">
                    <div class="card-content white-text">
                        <span class="card-title">Similar Missions</span>
                        <?php foreach($similarMissions as $d){ ?>
                            <div class="card blue-grey darken-1">
                                <div class="card-content white-text">
                                    <div class="row">
                                        <div class="col s7">
                                            <div class="venn" id="venn-<?php echo $d["id"] ?>"></div>
                                        </div>
                                        <div class="col s5"><a href="viewMission?id=<?php echo $d['id']?>" class="white-text"><?php echo $d["name"] ?></a><br/><h4><?php echo $d["crossover"] * 100 ?>% similarity</h4></div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- SIMILAR MISSIONS END -->

            </div>
        </div>
    </div>
</div>

<footer class="page-footer orange">

</footer>
<!--JavaScript at end of body for optimized loading-->
<script type="text/javascript" src="js/materialize.min.js"></script>
<script>
    function setGauge(value) {
        return {
            "type": "gauge",
            "background-color":"#b0bec5",
            "scale-r": {
                "aperture": 200,
                "values": "0:100:5",
                "center": {
                    "size": 5,
                    "background-color": "#66CCFF #FFCCFF",
                    "border-color": "none"
                },
                "ring": {  //Ring with Rules
                    "size": 10,
                    "rules": [
                        {
                            "rule": "%v >= 0 && %v <= 15",
                            "background-color": "red"
                        },
                        {
                            "rule": "%v >= 15 && %v <= 70",
                            "background-color": "orange"
                        },
                        {
                            "rule": "%v >= 70 && %v <= 100",
                            "background-color": "green"
                        }
                    ]
                }
            },
            "plot": {
                "csize": "5%",
                "size": "100%",
                "background-color": "#000000"
            },
            "series": [
                {"values": [value*100]}
            ]
        };
    }

    var associatedProducts, configGauge;
    associatedProducts = <?php echo json_encode($associatedProducts); ?>;

    var i;
    for (i = 0; i < associatedProducts.length; i++) {
        configGauge = setGauge(associatedProducts[i].missionScore);
        zingchart.render({
            id : 'gauge-'+associatedProducts[i].id.toString(),
            data : configGauge,
            height : "100%",
            width: "100%"
        });
    }


    function setVenn(join) {
        return {
            "type": "venn",
            "background-color":"#f5f5f5",
            "series": [
                {
                    "values": [100],
                    "join": [join * 100]
                },
                {
                    "values": [100],
                    "join": [join * 100]
                }
            ]
        }
    }

    var similarMissions, configVenn;
    similarMissions = <?php echo json_encode($similarMissions); ?>;

    var i;
    for (i = 0; i < similarMissions.length; i++) {
        configVenn = setVenn(similarMissions[i].crossover);
        zingchart.render({
            id : 'venn-'+similarMissions[i].id.toString(),
            data : configVenn,
            height : "100%",
            width: "100%"
        });
    }



    function setBlockPlot(strengthMin, strengthIQLow, strengthMed, strengthIQHigh, strengthMax) {
        return {
            "graphset": [
                {
                    "type": "hboxplot",
                    "background-color":"#f5f5f5",
                    "plotarea": {
                        "margin": "55"
                    },
                    "scaleX": {
                        // "line-color":"black",
                        "guide": {
                            "visible": false
                        },
                        "label": {
                            "text": ""
                        },
                        "values": ["1"]
                    },
                    "scaleY": {
                        // "line-color":"black",
                        "label": {
                            "text": "Percentage"
                        },
                        "values":[0,10,20,30,40,50,60,70,80,90,100]
                    },
                    tooltip: {
                        paddingBottom: 20
                    },
                    "options": {
                        "box": {
                            "barWidth": 0.5,
                            "tooltip": {
                                "text": "<br><b style=\"font-size:15px;\">Observations:</b><br><br>Maximum: <b>%data-max</b><br>Upper Quartile: <b>%data-upper-quartile</b><br>Median: <b>%data-median</b><br>Lower Quartile: <b>%data-lower-quartile</b><br>Minimum: <b>%data-min</b>"
                            }
                        },
                    },
                    "series": [
                        {
                            "dataBox": [[strengthMin, strengthIQLow, strengthMed, strengthIQHigh, strengthMax]],
                            // "dataOutlier": [[1, 24]]
                        }
                    ]
                }
            ]
        };
    }

    var configBlockPlot, strengthMin, strengthIQLow, strengthMed, strengthIQHigh, strengthMax, data;

    data = <?php echo json_encode($data); ?>;
    strengthMin = data.strengthMin * 100;
    strengthIQLow = data.strengthIQLow * 100;
    strengthMed = data.strengthMed * 100;
    strengthIQHigh = data.strengthIQHigh * 100;
    strengthMax = data.strengthMax * 100;

    // alert(strengthMin.toString()+","+strengthIQLow.toString()+","+strengthMed.toString()+","+strengthIQHigh.toString()+","+strengthMax.toString());
    configBlockPlot = setBlockPlot(strengthMin, strengthIQLow, strengthMed, strengthIQHigh, strengthMax);

    zingchart.render({
        id : 'myChart',
        data : configBlockPlot,
        height: "100%",
        width: "100%"
    });

</script>
</body>
</html>