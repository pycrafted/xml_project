<?php

namespace WhatsApp\Utils;

use DOMDocument;
use SimpleXMLElement;
use Exception;

/**
 * XMLManager - Gestionnaire central pour toutes les opérations XML
 * 
 * Cette classe fournit une interface unifiée pour :
 * - Charger et valider des fichiers XML
 * - Sauvegarder des données en XML
 * - Valider contre le schéma XSD
 * 
 * @author WhatsApp Clone Team
 * @version 1.0
 */
class XMLManager
{
    private const NAMESPACE_URI = 'http://whatsapp.clone/data';
    private const XSD_PATH = 'schemas/whatsapp_data.xsd';
    private const DATA_PATH = 'data/sample_data.xml';

    private DOMDocument $dom;
    private string $dataFile;
    private string $xsdFile;

    /**
     * Résout les chemins relatifs pour fonctionner depuis n'importe quel répertoire
     * 
     * @param string $path Chemin relatif
     * @return string Chemin absolu résolu
     */
    private function resolvePath(string $path): string
    {
        // Chemins possibles selon le contexte d'exécution
        $possiblePaths = [
            $path,                    // Chemin direct (depuis racine)
            '../' . $path,           // Depuis public/ 
            './' . $path,            // Chemin explicite
            dirname(dirname(__DIR__)) . '/' . $path, // Chemin absolu depuis src/
        ];
        
        foreach ($possiblePaths as $testPath) {
            if (file_exists($testPath)) {
                return realpath($testPath);
            }
        }
        
        // Si aucun chemin trouvé, retourner le chemin original
        return $path;
    }

    /**
     * Constructeur
     * 
     * @param string|null $dataFile Chemin vers le fichier XML de données
     * @param string|null $xsdFile Chemin vers le fichier XSD
     */
    public function __construct(?string $dataFile = null, ?string $xsdFile = null)
    {
        $this->dataFile = $dataFile ?? $this->resolvePath(self::DATA_PATH);
        $this->xsdFile = $xsdFile ?? $this->resolvePath(self::XSD_PATH);
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;
        
        // Initialiser le fichier XML s'il n'existe pas
        $this->initializeDataFile();
    }

    /**
     * Initialise le fichier XML avec une structure vide valide
     */
    private function initializeDataFile(): void
    {
        if (!file_exists($this->dataFile)) {
            $this->createEmptyDataFile();
        }
    }

    /**
     * Crée un fichier XML vide avec la structure de base
     */
    private function createEmptyDataFile(): void
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<whatsapp_data xmlns=\"" . self::NAMESPACE_URI . "\"\n";
        $xml .= "               xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
        $xml .= "               xsi:schemaLocation=\"" . self::NAMESPACE_URI . " " . $this->xsdFile . "\">\n";
        $xml .= "    <users></users>\n";
        $xml .= "    <contacts></contacts>\n";
        $xml .= "    <groups></groups>\n";
        $xml .= "    <messages></messages>\n";
        $xml .= "</whatsapp_data>\n";

        // Créer le répertoire data s'il n'existe pas
        $dataDir = dirname($this->dataFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        file_put_contents($this->dataFile, $xml);
    }

    /**
     * Charge et valide le fichier XML
     * 
     * @throws Exception Si le fichier est invalide
     * @return bool True si chargé avec succès
     */
    public function load(): bool
    {
        if (!file_exists($this->dataFile)) {
            throw new Exception("Fichier XML non trouvé : {$this->dataFile}");
        }

        libxml_use_internal_errors(true);
        
        if (!$this->dom->load($this->dataFile)) {
            $errors = libxml_get_errors();
            $errorMsg = "Erreur de parsing XML : ";
            foreach ($errors as $error) {
                $errorMsg .= $error->message;
            }
            throw new Exception($errorMsg);
        }

        if (!$this->validate()) {
            throw new Exception("XML invalide selon le schéma XSD");
        }

        return true;
    }

    /**
     * Valide le XML contre le schéma XSD
     * 
     * @return bool True si valide
     */
    public function validate(): bool
    {
        if (!file_exists($this->xsdFile)) {
            throw new Exception("Fichier XSD non trouvé : {$this->xsdFile}");
        }

        libxml_use_internal_errors(true);
        $isValid = $this->dom->schemaValidate($this->xsdFile);
        
        if (!$isValid) {
            $errors = libxml_get_errors();
            $errorMsg = "Erreurs de validation XSD :\n";
            foreach ($errors as $error) {
                $errorMsg .= "- " . trim($error->message) . "\n";
            }
            throw new Exception($errorMsg);
        }

        return true;
    }

    /**
     * Sauvegarde le DOM dans le fichier XML
     * 
     * @return bool True si sauvegardé avec succès
     */
    public function save(): bool
    {
        // Valider avant sauvegarde
        $this->validate();
        
        return $this->dom->save($this->dataFile) !== false;
    }

    /**
     * Retourne le DOMDocument pour manipulation directe
     * 
     * @return DOMDocument
     */
    public function getDom(): DOMDocument
    {
        return $this->dom;
    }

    /**
     * Retourne les données XML sous forme SimpleXML
     * 
     * @return SimpleXMLElement
     */
    public function getSimpleXML(): SimpleXMLElement
    {
        return simplexml_load_file($this->dataFile);
    }

    /**
     * Ajoute un élément avec le bon namespace
     * 
     * @param string $parentPath XPath du parent
     * @param string $elementName Nom de l'élément
     * @param array $data Données de l'élément
     * @return bool True si ajouté avec succès
     */
    public function addElement(string $parentPath, string $elementName, array $data): bool
    {
        $this->load();
        
        $xpath = new \DOMXPath($this->dom);
        $xpath->registerNamespace('wa', self::NAMESPACE_URI);
        
        $parentNode = $xpath->query($parentPath)->item(0);
        if (!$parentNode) {
            throw new Exception("Parent node non trouvé : {$parentPath}");
        }

        $newElement = $this->dom->createElementNS(self::NAMESPACE_URI, $elementName);
        
        // Ajouter les attributs
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $attr => $value) {
                $newElement->setAttribute($attr, $value);
            }
            unset($data['attributes']);
        }

        // Ajouter les éléments enfants
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Élément complexe
                $childElement = $this->dom->createElementNS(self::NAMESPACE_URI, $key);
                $this->addArrayToElement($childElement, $value);
                $newElement->appendChild($childElement);
            } else {
                // Élément simple
                $childElement = $this->dom->createElementNS(self::NAMESPACE_URI, $key, htmlspecialchars($value));
                $newElement->appendChild($childElement);
            }
        }

        $parentNode->appendChild($newElement);
        return $this->save();
    }

    /**
     * Aide à ajouter un array à un élément DOM
     */
    private function addArrayToElement(\DOMElement $element, array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Vérifier si c'est un tableau d'éléments avec attributs
                if (is_numeric($key)) {
                    // C'est un élément avec attributs
                    $childElement = $this->dom->createElementNS(self::NAMESPACE_URI, $element->parentNode->nodeName);
                    
                    if (isset($value['attributes'])) {
                        foreach ($value['attributes'] as $attr => $attrValue) {
                            $childElement->setAttribute($attr, $attrValue);
                        }
                        unset($value['attributes']);
                    }
                    
                    if (!empty($value)) {
                        $this->addArrayToElement($childElement, $value);
                    }
                    
                    $element->appendChild($childElement);
                } else {
                    // Élément normal
                    if (is_array($value) && isset($value[0]) && is_array($value[0]) && isset($value[0]['attributes'])) {
                        // Array d'éléments avec attributs
                        foreach ($value as $item) {
                            $childElement = $this->dom->createElementNS(self::NAMESPACE_URI, $key);
                            if (isset($item['attributes'])) {
                                foreach ($item['attributes'] as $attr => $attrValue) {
                                    $childElement->setAttribute($attr, $attrValue);
                                }
                            }
                            $element->appendChild($childElement);
                        }
                    } else {
                        $childElement = $this->dom->createElementNS(self::NAMESPACE_URI, $key);
                        $this->addArrayToElement($childElement, $value);
                        $element->appendChild($childElement);
                    }
                }
            } else {
                $childElement = $this->dom->createElementNS(self::NAMESPACE_URI, $key, htmlspecialchars($value));
                $element->appendChild($childElement);
            }
        }
    }

    /**
     * Trouve un élément par ID
     * 
     * @param string $elementType Type d'élément (user, contact, group, message)
     * @param string $id ID de l'élément
     * @return \DOMElement|null
     */
    public function findElementById(string $elementType, string $id): ?\DOMElement
    {
        $this->load();
        
        $xpath = new \DOMXPath($this->dom);
        $xpath->registerNamespace('wa', self::NAMESPACE_URI);
        
        $query = "//wa:{$elementType}[@id='{$id}']";
        $nodes = $xpath->query($query);
        
        return $nodes->length > 0 ? $nodes->item(0) : null;
    }

    /**
     * Supprime un élément par ID
     * 
     * @param string $elementType Type d'élément
     * @param string $id ID de l'élément
     * @return bool True si supprimé
     */
    public function deleteElementById(string $elementType, string $id): bool
    {
        $element = $this->findElementById($elementType, $id);
        if ($element) {
            $element->parentNode->removeChild($element);
            return $this->save();
        }
        return false;
    }
} 