<?php 

/**
 * Module to demonstrate the features of the sfProjectAnalyserPlugin.
 * Each example is illusatrated by a code, this code can be found in the paAlert class.
 *
 * @author COil
 * @since  V0.8.0 - 21/6/10
 * 
 * @see paAlert
 * @package    sfProjectAnalyserPlugin
 * @subpackage demo
 */
class sfProjectAnalyserActions extends sfActions
{
  /**
   * Method is public but is not a typical action method.
   */
  public function myAlert3001()
  {
  }

  // Alert for the number of actions into a module
  /** DOCBLOCK */public function executeAlert3002_1() {}
  /** DOCBLOCK */public function executeAlert3002_2() {}
  /** DOCBLOCK */public function executeAlert3002_3() {}
  /** DOCBLOCK */public function executeAlert3002_4() {}
  /** DOCBLOCK */public function executeAlert3002_5() {}
  /** DOCBLOCK */public function executeAlert3002_6() {}
  /** DOCBLOCK */public function executeAlert3002_7() {}
  /** DOCBLOCK */public function executeAlert3002_8() {}
  /** DOCBLOCK */public function executeAlert3002_9() {}
  /** DOCBLOCK */public function executeAlert3002_10() {}
  /** DOCBLOCK */public function executeAlert3002_11() {}
  /** DOCBLOCK */public function executeAlert3002_12() {}
  /** DOCBLOCK */public function executeAlert3002_13() {}
  /** DOCBLOCK */public function executeAlert3002_14() {}
  /** DOCBLOCK */public function executeAlert3002_15() {}
  /** DOCBLOCK */public function executeAlert3002_16() {}
  /** DOCBLOCK */public function executeAlert3002_17() {}
  /** DOCBLOCK */public function executeAlert3002_18() {}
  /** DOCBLOCK */public function executeAlert3002_19() {}
  /** DOCBLOCK */public function executeAlert3002_20() {}
  
  /**
   * Action that is too big.
   *
   * @param sfWebRequest $request
   */
  public function executeAlert4001(sfWebRequest $request) // 1
  {/* 2
      3
      4
      5
      6
      7
      8
      9
      10
      11
      12
      13
      14
      15
      16
      17
      18
      19
      20
      21
      22
      23
      24
      25
      26
      27
      28
      29
      30
   31 */ }

  public function executeAlert4002(sfWebRequest $request)
  {
    // Action without docblock
  }

  /**
   * Action with a sfContext::getInstance() call.
   */
  public function executeAlert4003(sfWebRequest $request)
  {
    $context = sfContext::getInstance();
  }

  /**
   * Example for the alert 4004.
   */
  public function executeAlert4004(sfWebRequest $request)
  {
    return $this->executeToto($request);
  }
  
  /**
   * Action that has a very big layout.
   *
   * @param sfWebRequest $request
   */
  public function executeAlert6001(sfWebRequest $request)
  {    
  }




}