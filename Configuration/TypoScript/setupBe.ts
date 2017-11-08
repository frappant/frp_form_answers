
# Module configuration
module.tx_frpformanswers {
  persistence {
    storagePid = {$module.tx_frpformanswers_formanswers.persistence.storagePid}
  }
  view {
    templateRootPaths.0 = EXT:frp_form_answers/Resources/Private/Backend/Templates/
    templateRootPaths.1 = {$module.tx_frpformanswers_formanswers.view.templateRootPath}
    partialRootPaths.0 = EXT:frp_form_answers/Resources/Private/Backend/Partials/
    partialRootPaths.1 = {$module.tx_frpformanswers_formanswers.view.partialRootPath}
    layoutRootPaths.0 = EXT:frp_form_answers/Resources/Private/Backend/Layouts/
    layoutRootPaths.1 = {$module.tx_frpformanswers_formanswers.view.layoutRootPath}
  }
}


[Frappant\FrpFormAnswers\Condition\BeUserHasAccessRightsCondition]
module.tx_form {
    settings {
        yamlConfigurations {
            1495003209 = EXT:frp_form_answers/Configuration/Yaml/BaseSetup.yaml
            1495003214 = EXT:frp_form_answers/Configuration/Yaml/FormEditorSetup.yaml
            1495003219 = EXT:frp_form_answers/Configuration/Yaml/FormEngineSetup.yaml
        }
    }
}
[end]