<?php

namespace SalesRender\Plugin\Instance\Excel\Components;

use SalesRender\Plugin\Components\Settings\Settings;

class UpdateSettings
{
    public static function updatesOutdatedFields()
    {
        $settingModel = Settings::find();
        $settings = $settingModel->getData();
        $arraySettings = $settings->all();
        $newFieldKeys = [];
        foreach ($arraySettings['main']['fields'] as $fieldKey) {
            $partsFieldKey = explode(".", $fieldKey);

            preg_match('/\[(.*?)\]/', $fieldKey, $matches);
            $fieldName = '';
            if (isset($matches[1])) {
                $fieldName = $matches[1];
            }
            [,$fieldType] = $partsFieldKey;
            if ($fieldType !== "phoneFields") {
                $newFieldKeys[] = $fieldKey;
                continue;
            }

            [$preLastKey, $lastKey] = array_slice($partsFieldKey, -2, 2);

            if ($lastKey === "duplicates") {
                $newFieldKeys[] = "contacts.phones.[$fieldName].duplicates";
                continue;
            }

            $newFieldKeys[] = ($preLastKey === "phone") ? "data.phoneFields.[$fieldName].value.$lastKey" : $fieldKey;
        }

        if (json_encode($arraySettings['main']['fields']) !== json_encode($newFieldKeys)) {
            $settings->set('main.fields', $newFieldKeys);
            $settingModel->setData($settings);
            $settingModel->save();
        }
    }
}