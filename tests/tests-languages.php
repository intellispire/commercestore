<?php


/**
 * @group cs_languages
 */
class Tests_Languages extends CS_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @todo: Remove test. No languages are currently supported.
	 * @since 4.0
	 * @return void
	 */
	public function test_included_languages() {
		// As we work towards getting files included into language packs on WordPress.org, this allows us
		// to make sure we don't keep including translations that hit 100% and shoudl be removed
		$this->assertTrue( true );
/*
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-af.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-an.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-az.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-be.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-bg_BG.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-bn_BD.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-bs_BA.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ca.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-co.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-cs_CZ.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-cy.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-da_DK.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-de_CH.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-de_DE.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-el.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-eo.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-es_AR.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-es_CL.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-es_ES.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-es_MX.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-es_PE.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-es_VE.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-et.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-eu.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-fa.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-fa_IR.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-fi.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-fo.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-fy.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ga.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-gd.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-gl_ES.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-he_IL.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-hi_IN.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-hr.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-hu_HU.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-id_ID.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-is_IS.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-it_IT.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ja.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-jv.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ka.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ka_GE.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-kk.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-km.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-kn.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ko_KR.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ky.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-lo.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-lt_LT.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-lv.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-mg.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-mk_MK.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-mn.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ms_MY.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-my_MM.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-nb_NO.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ne_NP.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-nl_NL.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-nn_NO.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-oc.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-os.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-pl_PL.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ps.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-pt_BR.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-pt_PT.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ro_RO.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ru_RU.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-sah.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-si_LK.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-sk_SK.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-sl_SI.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-so.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-sq.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-sr_RS.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-su.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-sv_SE.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-sw.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ta_IN.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ta_LK.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-te.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-tg.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-th.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-tl.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-tr_TR.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ug.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-uk.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-ur.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-uz.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-vi.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-zh_CN.mo') );
		$this->assertTrue( file_exists( CS_PLUGIN_DIR . '/languages/commercestore-zh_TW.mo') );
     */
	}

}
