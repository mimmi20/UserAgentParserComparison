<?php
use UserAgentParser\Exception;
use UserAgentParser\Exception\NoResultFoundException;
use UserAgentParserComparison\Entity\Result;
use Ramsey\Uuid\Uuid;

include_once 'bootstrap.php';

/* @var $chain \UserAgentParser\Provider\Chain */
$chain = include 'bin/getChainProvider.php';

/* @var $entityManager \Doctrine\ORM\EntityManager */
$conn = $entityManager->getConnection();

$providerRepo = $entityManager->getRepository('UserAgentParserComparison\Entity\Provider');
$resultRepo = $entityManager->getRepository('UserAgentParserComparison\Entity\Result');

/*
 * Load providers
 */
$providers = [];
foreach ($chain->getProviders() as $provider) {
    $providerEntity = $providerRepo->findOneBy([
        'name' => $provider->getName()
    ]);
    
    if ($providerEntity === null) {
        throw new \Exception('no provider found with name: ' . $provider->getName());
    }
    
    $providers[$provider->getName()] = $providerEntity;
}

echo 'load agents...' . PHP_EOL;

/*
 * load userAgents...
 */
$sql = "
    SELECT
        *
    FROM userAgent
    LEFT JOIN result 
        ON userAgent_id = uaId
        AND provider_id = '9b0fa449-ec1b-40c8-8b1c-9486eb3b9cbc'
    WHERE 
        resId IS NULL
    ORDER BY uaId
";
$statement = $conn->prepare($sql);
$statement->execute();

echo 'done loading..' . PHP_EOL;

$conn->beginTransaction();
$currenUserAgent = 1;

while ($row = $statement->fetch()) {
    foreach ($chain->getProviders() as $provider) {
        /* @var $provider \UserAgentParser\Provider\AbstractProvider */
        
        /* @var $providerEntity \UserAgentParserComparison\Entity\Provider */
        $providerEntity = $providers[$provider->getName()];
        
        $sql = "
            SELECT
                *
            FROM result
            WHERE
                userAgent_id = '" . $row['uaId'] . "'
                AND provider_id = '" . $providerEntity->id . "'
        ";
        $result = $conn->fetchAll($sql);
        
        if (count($result) === 1) {
            $row2 = $result[0];
            
            // skip
            if ($row2['resProviderVersion'] == $provider->getVersion() || $provider->getVersion() === null) {
                echo 'S';
                continue;
            }
        } else {
            $row2 = [
                'provider_id' => (string) $providerEntity->id,
                'userAgent_id' => $row['uaId']
            ];
        }
        
        /*
         * Get the result with timing
         */
        $start = microtime(true);
        
        try {
            $result = $provider->parse($row['uaString']);
        } catch (NoResultFoundException $ex) {
            $result = null;
        } catch (\UserAgentParser\Exception\RequestException $ex) {
            echo $ex->getMessage() . PHP_EOL;
            
            continue;
        }
        
        $end = microtime(true);
        
        $row2['resProviderVersion'] = $provider->getVersion();
        $row2['resParseTime'] = $end - $start;
        $date = new \DateTime(null, new \DateTimeZone('UTC'));
        $row2['resLastChangeDate'] = $date->format('Y-m-d H:i:s');
        
        /*
         * Hydrate the result
         */
        if ($result === null) {
            $row2['resResultFound'] = 0;
        } else {
            $row2['resResultFound'] = 1;
            
            $row2 = hydrateResult($row2, $result);
        }
        
        /*
         * Persist
         */
        if (! isset($row2['resId'])) {
            $row2['resId'] = Uuid::uuid4()->toString();
            
            $conn->insert('result', $row2);
        } else {
            $conn->update('result', $row2, [
                'resId' => $row2['resId']
            ]);
        }
        
        echo '.';
    }
    
    if ($currenUserAgent % 100 === 0) {
        $conn->commit();
        
        $conn->beginTransaction();
    }
    
    // display "progress"
    echo ' - Count: ' . $currenUserAgent . PHP_EOL;
    
    $currenUserAgent ++;
}

if ($conn->getTransactionNestingLevel() !== 0) {
    $conn->commit();
}

function hydrateResult(array $row2, \UserAgentParser\Model\UserAgent $result)
{
    $toHydrate = [
        'resBrowserName' => $result->getBrowser()->getName(),
        'resBrowserVersion' => $result->getBrowser()
            ->getVersion()
            ->getComplete(),
        
        'resEngineName' => $result->getRenderingEngine()->getName(),
        'resEngineVersion' => $result->getRenderingEngine()
            ->getVersion()
            ->getComplete(),
        
        'resOsName' => $result->getOperatingSystem()->getName(),
        'resOsVersion' => $result->getOperatingSystem()
            ->getVersion()
            ->getComplete(),
        
        'resDeviceModel' => $result->getDevice()->getModel(),
        'resDeviceBrand' => $result->getDevice()->getBrand(),
        'resDeviceType' => $result->getDevice()->getType(),
        'resDeviceIsMobile' => $result->getDevice()->getIsMobile(),
        'resDeviceIsTouch' => $result->getDevice()->getIsTouch(),
        
        'resBotIsBot' => $result->getBot()->getIsBot(),
        'resBotName' => $result->getBot()->getName(),
        'resBotType' => $result->getBot()->getType(),
        
        'resRawResult' => serialize($result->toArray(true)['providerResultRaw'])
    ];
    
    return array_merge($row2, $toHydrate);
}
