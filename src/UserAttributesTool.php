<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Skyline\Admin\Tool;


use Skyline\Admin\Tool\Attribute\AbstractAttribute;
use Skyline\Admin\Tool\Attribute\AttributeInterface;
use Skyline\Admin\Tool\Attribute\Value\FileValueContainer;
use Skyline\Admin\Tool\Attribute\Value\ValueContainer;
use Skyline\Admin\Tool\Event\AttributeEvent;
use Skyline\CMS\Security\Tool\AbstractSecurityTool;
use Skyline\Kernel\Service\SkylineServiceManager;
use Skyline\PDO\PDOResourceInterface;
use TASoft\Service\ServiceManager;
use TASoft\Util\PDO;
use TASoft\Util\ValueInjector;

class UserAttributesTool extends AbstractSecurityTool
{
	const SERVICE_NAME = 'attributeTool';

	const ATTR_LOGO_ID = 1;
	const ATTR_DEPARTEMENT_ID = 2;
	const ATTR_STATUS_ID = 3;
	const ATTR_OPTIONS_ID = 4;
	const ATTR_EMAIL_ID = 5;
	const ATTR_WWW_ID = 6;
	const ATTR_WHATSAPP_ID = 7;
	const ATTR_FACEBOOK_ID = 8;
	const ATTR_TWITTER_ID = 9;
	const ATTR_YOUTUBE_ID = 10;
	const ATTR_INSTAGRAM_ID = 11;
	const ATTR_SNAPCHAT_ID = 12;
	const ATTR_LINKEDIN_ID = 13;
	const ATTR_ADDRESS_ID = 14;
	const ATTR_BIRTHDATE_ID = 15;
	const ATTR_TELEFON_ID = 16;
	const ATTR_MOBILE_ID = 17;

	/** @var PDO */
	private $PDO;

	private $cachedAttributes;
	private $attributeName2ID;
	private $attribute2Group;
	private $group2Attribute;
	private $cachedAttributeGroups;
	private $cachedAttributeGroupNames2ID;

	private $boundFilesMap = [];

	/**
	 * SecurityTool constructor.
	 * @param $PDO
	 * @param $boundFilesMap
	 * @param $withEvents
	 */
	public function __construct($PDO, $boundFilesMap, $withEvents = true)
	{
		$this->PDO = $PDO;
		$this->boundFilesMap = $boundFilesMap;
		if(!$withEvents)
			$this->disableEvents();
	}

	/**
	 * @return PDO
	 */
	public function getPDO(): PDO
	{
		return $this->PDO;
	}

	/**
	 * Gets all available attributes
	 *
	 * @return AttributeInterface[]
	 */
	public function getAttributes() {
		if(NULL === $this->cachedAttributes) {
			$this->cachedAttributes = [];
			foreach($this->PDO->select("SELECT
    SKY_USER_ATTRIBUTE.id,
    valueType,
    SKY_USER_ATTRIBUTE.name,
    SKY_USER_ATTRIBUTE.description,
    icon,
       multiple,
       enabled,
       SKY_USER_ATTRIBUTE_GROUP.name as groupName,
       SKY_USER_ATTRIBUTE_GROUP.description as groupDescription,
       SKY_USER_ATTRIBUTE_GROUP.id as gid
FROM SKY_USER_ATTRIBUTE
LEFT JOIN SKY_USER_ATTRIBUTE_GROUP on attr_group = SKY_USER_ATTRIBUTE_GROUP.id
ORDER BY indexing, name") as $record) {
				$attr = AbstractAttribute::create($record);
				if ($attr) {
					$this->cachedAttributes[$record["id"] * 1] = $attr;
					$this->attributeName2ID[strtolower($record["name"])] = $record["id"] * 1;

					if($gid = $record["gid"]) {
						$this->cachedAttributeGroups[$gid*1]["name"] = $name = $record["groupName"];
						$this->cachedAttributeGroups[$gid*1]["description"] = $record["groupDescription"];
						$this->cachedAttributeGroupNames2ID[strtolower($name)] = $gid*1;
						$this->attribute2Group[$attr->getId()] = $gid*1;
						$this->group2Attribute[$gid*1][] = $attr->getId();
					}
				} else
					trigger_error("Can not create user attribute {$record["name"]}", E_USER_NOTICE);
			}
		}
		return $this->cachedAttributes;
	}

	/**
	 * Gets all enabled attributes
	 *
	 * @return array
	 */
	public function getEnabledAttributes() {
		$list = [];
		foreach($this->getAttributes() as $idx => $attribute) {
			if($attribute->isEnabled())
				$list[$idx] = $attribute;
		}
		return $list;
	}

	/**
	 * Gets information about an attribute group
	 *
	 * @param $group
	 * @param null $name
	 * @param null $description
	 * @return int
	 */
	public function getGroup($group, &$name = NULL, &$description = NULL): int {
		$this->getAttributes();
		if(!is_numeric($group))
			$group = $this->cachedAttributeGroupNames2ID[ strtolower($group) ] ?? -1;
		$g = $this->cachedAttributeGroups[$group] ?? NULL;
		$name = $g["name"] ?? NULL;
		$description = $g["description"] ?? NULL;
		return $group;
	}

	/**
	 * Gets attributes by groups
	 *
	 * @param $group
	 * @param bool $enabledOnly
	 * @return array
	 */
	public function getAttributesByGroup($group, bool $enabledOnly = true) {
		$list = [];
		$gid = $this->getGroup($group);

		foreach(($this->group2Attribute[$gid] ?? []) as $aid) {
			/** @var AttributeInterface $attr */
			$attr = $this->cachedAttributes[$aid];
			if(!$enabledOnly || $attr->isEnabled())
				$list[$aid] = $attr;
		}

		return $list;
	}

	/**
	 * @param $attribute
	 * @return int
	 */
	public function getAttributeID($attribute): int {
		$this->getAttributes();

		if($attribute instanceof AttributeInterface)
			return $attribute->getID();
		if(is_string($attribute))
			return $this->cachedAttributeGroupNames2ID[ strtolower($attribute) ] ?? -1;
		return $attribute * 1;
	}

	/**
	 * Fetches a user attribute
	 *
	 * @param string|int $attribute
	 * @return AttributeInterface|null
	 */
	public function getAttribute($attribute): ?AttributeInterface {
		$this->getAttributes();
		if(!is_numeric($attribute))
			$attribute = $this->attributeName2ID[ strtolower($attribute) ] ?? -1;
		return $this->cachedAttributes[$attribute] ?? NULL;
	}

	/**
	 * Fetches user value for a specific attribute.
	 * This method always returns a value container if the attribute exists.
	 *
	 * @param $attribute
	 * @param $user
	 * @param bool $rawValue        If set, returns the raw value without value container
	 * @return null|ValueContainer|array|mixed
	 */
	public function getAttributeValue($attribute, $user, bool $rawValue = false) {
		if($attr = $this->getAttribute($attribute)) {
			if($user instanceof PDOResourceInterface)
				$user = $user->getID();

			if(is_numeric($user)) {
				$aid = $attr->getID();

				$theOptions = 0;
				$theValue = NULL;

				foreach($this->PDO->select("SELECT options, value FROM SKY_USER_ATTRIBUTE_Q WHERE user = $user AND attribute = $aid") as $record) {
					$theOptions |= $record["options"];

					$v = $record["value"];
					$v = $attr->convertValueFromDB($v);
					if($attr->allowsMultiple()) {
						$theValue[] = $v;
					} else {
						$theValue = $v;
						break;
					}
				}

				if($rawValue) {
					return $theValue;
				}

				if($map = $this->boundFilesMap[ $aid ] ?? false) {
					$map = realpath(ServiceManager::generalServiceManager()->mapValue($map));
					if ($map && file_exists($file = "$map/$theValue")) {
						$value = new FileValueContainer();
						$value->setFilename($file);
					}
				}

				if(!isset($value))
					$value = new ValueContainer();

				$vi = new ValueInjector($value, ValueContainer::class);
				$vi->attribute = $attr;
				$vi->value = $theValue;
				$vi->options = $theOptions;

				return $value;
			} else
				trigger_error("Can not get user id", E_USER_WARNING);
		}
		return NULL;
	}


	/**
	 * @param $attribute
	 * @return ValueContainer|null
	 */
	public function makeAttributeValue($attribute): ?ValueContainer {
		if($attr = $this->getAttribute($attribute)) {
			$value = new ValueContainer();
			$vi = new ValueInjector($value, ValueContainer::class);
			$vi->attribute = $attr;
			return $value;
		}
		return NULL;
	}

	/**
	 * @param $attribute
	 * @param $filename
	 * @param string $newName
	 * @param string $copyFunc
	 * @return FileValueContainer|null
	 */
	public function makeFileAttributeValue($attribute, $filename, $newName = '', $copyFunc = ''): ?FileValueContainer {
		if($attr = $this->getAttribute($attribute)) {
			if($map = $this->boundFilesMap[$attr->getID()]) {
				$map = realpath(ServiceManager::generalServiceManager()->mapValue( $map ));
				if($map && is_file($filename)) {
					$newName = $newName ?: basename($filename);
					$dst = "$map/$newName";
					if(is_file($dst) || ($copyFunc && $copyFunc($filename, $dst))) {
						$value = new FileValueContainer();
						$value->setFilename( $dst );
						$vi = new ValueInjector($value, ValueContainer::class);
						$vi->value = $newName;
						$vi->attribute = $attr;
						unset($vi);
						return $value;
					}
				}
			}
		}
		return NULL;
	}

	/**
	 * @param ValueContainer $value
	 * @param $user
	 * @return bool
	 */
	public function updateAttributeValue(ValueContainer $value, $user): bool {
		if($user instanceof PDOResourceInterface)
			$user = $user->getID();

		if(is_numeric($user)) {
			$aid = $value->getAttribute()->getID();
			$this->PDO->exec("DELETE FROM SKY_USER_ATTRIBUTE_Q WHERE user = $user AND attribute = $aid");

			$insert = $this->PDO->inject("INSERT INTO SKY_USER_ATTRIBUTE_Q (user, attribute, options, value) VALUES ($user, $aid, ?, ?)");

			if($value->getAttribute()->allowsMultiple() && is_iterable($value->getValue())) {
				foreach($value->getValue() as $v) {
					$insert->send([
						$value->getOptions(),
						$value->getAttribute()->convertValueToDB( $v )
					]);
				}
			} else {
				$insert->send([
					$value->getOptions(),
					$value->getAttribute()->convertValueToDB( $value->getValue() )
				]);
			}

			if(!$this->disableEvents) {
				$e = new AttributeEvent();
				$e->setAttribute($value);
				SkylineServiceManager::getEventManager()->trigger(SKY_EVENT_USER_ATTRIBUTE_UPDATE, $e, $value);
			}

			return true;
		}
		return false;
	}

	/**
	 * Removes a user attribute value
	 *
	 * @param $attribute
	 * @param $user
	 * @return bool
	 */
	public function removeAttributeValue($attribute, $user) {
		if($aid = $this->getAttributeID($attribute)) {

			if(!$this->disableEvents) {
				$e = new AttributeEvent();
				$e->setAttribute($attribute);
				SkylineServiceManager::getEventManager()->trigger(SKY_EVENT_USER_ATTRIBUTE_REMOVE, $e, $attribute, $user);
			}

			if($user instanceof PDOResourceInterface)
				$user = $user->getID();

			if(is_numeric($user)) {
				if($map = $this->boundFilesMap[ $aid ] ?? false) {
					$map = realpath(ServiceManager::generalServiceManager()->mapValue( $map ));
					if($map) {
						foreach($this->PDO->select("SELECT value FROM SKY_USER_ATTRIBUTE_Q WHERE user = $user AND attribute = $aid") as $record) {
							$value = $record["value"];

							if(file_exists($file = "$map/$value")) {
								unlink($file);
							}
						}
					}
				}

				$this->PDO->exec("DELETE FROM SKY_USER_ATTRIBUTE_Q WHERE user = $user AND attribute = $aid");
				return true;
			}
		}
		return false;
	}
}