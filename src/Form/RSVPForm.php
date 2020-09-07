<?php
/**
 * @file
 * Contains \Drupal\rsvplist\Form\RSVPForm
 */
namespace Drupal\rsvplist\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an RSVP Email form.
 */
class RSVPForm extends FormBase{
    /**
     * (@inheritdoc)
     */
    public function getFormId(){
        return 'rsvplist_email_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state){
        $node = \Drupal::routeMatch()->getParameter('node');
        $nid = $node->nid->value;
        $form['email'] = array(
            '#title' => t('Email Address'),
            '#type' => 'textfield',
            '#size' => 25,
            '#description' => t("We'll send updates to the email that you provide"),
            '#required' => TRUE,
        );
        
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('RSVP'),
        );

        $form['nid'] = array(
            '#type' => 'hidden',
            '#value' => $nid,
        );

        return $form;

    }

    /**
     * (@inheritdoc)
     * 
     * I notice that the email validator code is not working as expected. It lets '0' pass as a valid email address
     * as well as email such as 'test@gmail' . Need to check how the drupal email validation algorithm works
     */

    public function validateForm(array &$form, FormStateInterface $form_state){
        $value = $form_state->getValue('email');
        if($value == !\Drupal::service('email.validator')->isValid($value)){
            //if the email in $value is not valid based on drupal's email validator service
            $form_state->setErrorByName('email',t('The email address is not valid.', array('%mail'=>$value)));
            return;
        }
        $node = \Drupal::routeMatch()->getParameter('node');
        // Check if email already is set for this node
        $select = Database::getConnection()->select('rsvplist', 'r');
        $select->fields('r', array('nid'));
        $select->condition('nid', $node->id());
        $select->condition('mail', $value);
        $results = $select->execute();
        if (!empty($results->fetchCol())) {
          // We found a row with this nid and email.
          $form_state->setErrorByName('email', t('The address %mail is already subscribed to this list.', array('%mail' => $value)));
        }
    
    }

    /**
     * (@inheritdoc)
     */
    public function submitForm(array &$form, FormStateInterface $form_state ){
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        db_insert('rsvplist')
          ->fields(array(
              'mail' => $form_state->getValue('email'),
              'nid' => $form_state->getValue('nid'),
              'uid' => $user->id(),
              'created' => time(),
          ))
          ->execute();
        drupal_set_message(t('Thank you for your RSVP. You are on the list for the event'));
        
    }

}
