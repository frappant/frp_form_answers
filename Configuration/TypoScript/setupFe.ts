plugin.tx_form {
    settings {
        yamlConfigurations {
            1495003309 = EXT:frp_form_answers/Configuration/Yaml/BaseSetup.yaml
            1495006219 = EXT:frp_form_answers/Configuration/Yaml/FormEngineSetup.yaml
        }
        finishers{
          1495303309{
            class = Frappant\FrpFormAnswers\Domain\Finishers\SaveFormToDatabaseFinisher
          }
        }
    }
}

config.tx_extbase.persistence.classes {
    Frappant\FrpFormAnswers\Domain\Model\FormEntry {
        mapping {
            columns.crdate.mapOnProperty = crdate
        }
    }
}