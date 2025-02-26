<?php

namespace leantime\domain\services {

    use leantime\core;
    use leantime\domain\repositories;
    use DatePeriod;
    use DateTime;
    use DateInterval;

    class wiki
    {
        private $wikiRepository;

        public function __construct(repositories\wiki $wikiRepository)
        {
            $this->wikiRepository = $wikiRepository;
        }

        public function getArticle($id, $projectId = null)
        {

            if (!is_null($id)) {
                $article = $this->wikiRepository->getArticle($id, $projectId);

                if (!$article) {
                    $article = $this->wikiRepository->getArticle(-1, $projectId);
                }
            } else {
                $article = $this->wikiRepository->getArticle(-1, $projectId);
            }


            return $article;
        }

        public function getAllProjectWikis($projectId)
        {
            return $this->wikiRepository->getAllProjectWikis($projectId);
        }

        public function getAllWikiHeadlines($wikiId, $userId)
        {
            return $this->wikiRepository->getAllWikiHeadlines($wikiId, $userId);
        }

        public function getWiki($id)
        {
            return $this->wikiRepository->getWiki($id);
        }

        public function createWiki(\leantime\domain\models\wiki $wiki)
        {
            return $this->wikiRepository->createWiki($wiki);
        }

        public function updateWiki(\leantime\domain\models\wiki $wiki, $wikiId)
        {
            return $this->wikiRepository->updateWiki($wiki, $wikiId);
        }

        public function createArticle(\leantime\domain\models\wiki\article $article)
        {
            return $this->wikiRepository->createArticle($article);
        }

        public function updateArticle(\leantime\domain\models\wiki\article $article)
        {
            return $this->wikiRepository->updateArticle($article);
        }
    }

}
