<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Endroid\Twitter\Twitter;

/**
 * @package AppBundle\Controller
 */
class SocialWelcomeController extends Controller
{

    /**
     * @return Response
     * @Route("hello", name="social_welcome")
     *
     * A simple controller to do stuff specific to people logging in via social media buttons
     * Directed here via security.yml default_target_path
     */
    public function socialWelcomeAction()
    {
//        $this->addFlash('success', "Welcome back!");

        $user = $this->getUser();

//        $consumerKey = $this->getParameter('twitter_consumer_key');
//        $consumerSecret = $this->getParameter('twitter_consumer_secret');
//        $accessToken = $user->getTwitterAccessToken();
//        $accessTokenSecret = $user->getTwitterAccessTokenSecret();
//
//        $twitter = new Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
//
//        $tweet = ['status' => "test tweet"];
//        $twitter->query('/statuses/update', 'POST', 'json', $tweet);

        return $this->redirectToRoute('fos_user_profile_show');
    }

}
