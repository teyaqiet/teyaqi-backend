<?php

namespace App\AI\Formatters;


class MarkdownFormatter
{


    public function playerReport(array $data): string
    {


        $text = "### 👥 Player Report\n\n";


        $overview = $data['overview'];


        $text .= "### Overview\n\n";

        $text .= "* **Total Players:** "
            .$overview['total_players']."\n";

        $text .= "* **New Today:** "
            .$overview['new_players_today']."\n";

        $text .= "* **New This Week:** "
            .$overview['new_players_this_week']."\n";

        $text .= "* **New This Month:** "
            .$overview['new_players_this_month']."\n\n";





        $activity=$data['activity'];


        $text .= "### Activity\n\n";

        foreach($activity as $key=>$value){

            $label =
            ucwords(
                str_replace('_',' ',$key)
            );


            $text .= "* **{$label}:** {$value}\n";

        }



        $text .= "\n";






        $engagement=$data['engagement'];


        $text .= "### Engagement\n\n";


        foreach($engagement as $key=>$value){

            $label =
            ucwords(
                str_replace('_',' ',$key)
            );


            $text .= "* **{$label}:** {$value}\n";

        }



        $text .= "\n";





        $text .= "### 🏆 Top Players\n\n";


        foreach(
            $data['top_players']
            as $index=>$player
        ){

            $rank=$index+1;


            $text .=
            "{$rank}. **{$player['name']}** "
            ."(@{$player['username']})\n";


            $text .=
            "   * XP: "
            .number_format($player['total_xp'])
            ." | SR: "
            .$player['current_sr']
            ." | Level "
            .$player['level_data']['level']
            ." ({$player['level_data']['title']})\n\n";


        }



        return $text;


    }


}