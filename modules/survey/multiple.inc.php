<?php
/***************************************************************************
 *  Copyright (C) 2003-2011 Polytechnique.org                              *
 *  http://opensource.polytechnique.org/                                   *
 *                                                                         *
 *  This program is free software; you can redistribute it and/or modify   *
 *  it under the terms of the GNU General Public License as published by   *
 *  the Free Software Foundation; either version 2 of the License, or      *
 *  (at your option) any later version.                                    *
 *                                                                         *
 *  This program is distributed in the hope that it will be useful,        *
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of         *
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          *
 *  GNU General Public License for more details.                           *
 *                                                                         *
 *  You should have received a copy of the GNU General Public License      *
 *  along with this program; if not, write to the Free Software            *
 *  Foundation, Inc.,                                                      *
 *  59 Temple Place, Suite 330, Boston, MA  02111-1307  USA                *
 ***************************************************************************/

class SurveyQuestionMultiple extends SurveyQuestion
{
    public function __construct(Survey $survey)
    {
        parent::__construct($survey);
        $this->type = "multiple";
    }

    protected function buildAnswer(SurveyAnswer $answer, PlDict $data)
    {
        $content = $data->v($this->qid);
        $value   = $content['answer'];
        if (empty($value)) {
            $answer->answer = null;
            return true;
        }
        $id = to_integer($value);
        if ($id === false) {
            if ($value != 'other') {
                $answer->answers = null;
                return false;
            }
            if (@$this->parameters['allow_other']) {
                $answer->answer = array('other' => $content['text']);
            }
        } else {
            if ($id >= count($this->parameters['answers'])) {
                $answer->answers = null;
                return false;
            }
            $answer->answer = array('answer' => $id);
        }
        return true;
    }
}

?>