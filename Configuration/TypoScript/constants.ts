
module.tx_frpformanswers_formanswers {
  view {
    # cat=module.tx_frpformanswers_formanswers/file; type=string; label=Path to template root (BE)
    templateRootPath = EXT:frp_form_answers/Resources/Private/Backend/Templates/
    # cat=module.tx_frpformanswers_formanswers/file; type=string; label=Path to template partials (BE)
    partialRootPath = EXT:frp_form_answers/Resources/Private/Backend/Partials/
    # cat=module.tx_frpformanswers_formanswers/file; type=string; label=Path to template layouts (BE)
    layoutRootPath = EXT:frp_form_answers/Resources/Private/Backend/Layouts/
  }
  persistence {
    # cat=module.tx_frpformanswers_formanswers//a; type=string; label=Default storage PID
    storagePid =
  }
}
