<?php

   require_once 'cronInit.php'; //ABCD Cron/CLI functionality, limited routing
   require_once 'MPDF57/mpdf.php';

    function checkFrequency($freq) {
        //check if report needs to be run today;
            $dayOfWeek = date('D');
            $dateOfMonth = date('d');
            $go = FALSE;
            
            switch ($freq) {
                case 'daily'  : $go = TRUE; break;
                case 'weekly' : if ($dayOfWeek == 'Mon') { $go = TRUE; } ; break;
                case 'monthly': if ($dateOfMonth == '01') { $go = TRUE;} ; break;
                default: break;
            }
            return $go;
    }

    class StoredReport {

        protected $freq, $go;
        protected $recipIDs = array();
        
        public function __construct($recipString, $options, $freq) {
            $this->recipIDs = $this->_processIDs($recipString);
            $this->content = $this->_processOptions($options);
            $this->freq = $freq;
            $this->go = FALSE;
        }
        
        protected function _filterbyLevel(array $list, $level) {
                $result = array();
                $userTable = new Application_Model_DbTable_Users;
                
                switch (trim($level)) {
                    case 'All staff' : $levelID = 1; break;
                    case 'Managers'  : $levelID = 2; break;
                }
                
                foreach ($list as $uid) {
                    $userRecord = $userTable->getRecord($uid);
                    $userRole=$userRecord['role'];
                    if ($userRole >= $levelID) {
                        array_push($result,$uid);
                    }
                }
                
                return $result;
        }
        
        public function _processIDs($recipString) {
                $recipIDs = array();
                $keepGoing = TRUE;
                $validTypes = array('single','prgs','depts','grps');
                $recipArray = json_decode($recipString, TRUE); //TRUE to return array instead of object
                foreach ($recipArray as $recipRecord) {
                    $type = $recipRecord['subtype'];
                    $rid = $recipRecord['typeID'];
                    
                    if (!in_array($type,$validTypes)) {
                        throw new exception (
                                "Invalid recipient type $type passed to report builder."
                                );
                    }
                    
                    switch ($type) {
                        case 'single':  array_push($recipIDs,$recipRecord['typeID']); 
                                        $keepGoing = FALSE;
                                        break;
                        case 'prgs'  :  $typeTable = new Application_Model_DbTable_UserPrograms;                                
                                        $keepGoing = TRUE;
                                        break;     
                        case 'depts' :  $typeTable = new Application_Model_DbTable_UserDepartments;
                                        $keepGoing = TRUE;
                                        break;
                        default:        break;
                    }
                    
                    if ($keepGoing) {
                        $userIDs = $typeTable->getList('users',$rid);
                        $filteredIDs = $this->_filterByLevel($userIDs, $recipRecord['level']);
                        $recipIDs = array_merge($filteredIDs, $recipIDs);
                        unset($typeTable);
                    }
                }
                return array_unique($recipIDs);
        }
        
        protected function _processOptions($optionString) {
                $optionArray = json_decode($optionString,TRUE);
                return $optionArray;
        }

        protected function getProgData($typeID, $db, $data_group, $beginTime) {

            $data = array();
            $select = $db->select()
                         ->from('participantPrograms')
                         ->where('programID = ?', $typeID);
            $rows = $db->fetchAll($select);
            
            if ($rows != null) {
            
                // check max date
                foreach ($rows as $row) { $data_tmp[$row['participantID']][] = $row; }
                $rows = $data_tmp;
                $arr_tmp = array();
                foreach ($rows as $row) {
                    if (count($row) > 1) {
                        $date_max = new DateTime('2000-01-01 00:00');
                        foreach ($row as $r) {
                            $date_tmp = new DateTime($r['statusDate']);
                            if ($date_tmp > $date_max) { $date_max = $date_tmp; $rw = $r; }
                        }
                        $arr_tmp[] = $rw;
                    } else { $arr_tmp[] = $row[0]; }
                }
                $rows = $arr_tmp;
                $arr_tmp = array();
                $part_total = 0;
                
                $part_act = 0; 
                $part_wait = 0; 
                $part_conc = 0;
                
                $part_act_add = 0; 
                $part_wait_add = 0; 
                $part_conc_add = 0;
                
                $part_act_rem = 0; 
                $part_wait_rem = 0; 
                $part_conc_rem = 0;
                
                foreach ($rows as $row) {
                    $part_total++;
                    switch ($row['status']) {
                        case 'active':
                            $part_act++;
                            if ($row['prevStatus'] != '') { 
                                $part_act_add++; 
                            } else { 
                                $part_act_rem++; 
                            }
                            break;
                        case 'waitlist':
                            $part_wait++;
                            if ($row['prevStatus'] != '') { 
                                $part_wait_add++; 
                            } else { 
                                $part_wait_rem++; 
                            }
                            break;
                        default:
                            $part_conc++;
                            if ($row['prevStatus'] != '') { 
                                $part_conc_add++; 
                            } else { 
                                $part_conc_rem++; 
                            }
                            break;
                    }
                }
                
                $act_end = $part_act;
                $act_add = $part_act_add;
                $act_sub = $part_act_rem;
                $act_start = $act_end +$act_sub-$act_add;

                $wt_end = $part_wait;
                $wt_add = $part_wait_add;
                $wt_sub = $part_wait_rem;
                $wt_start = $wt_end +$wt_sub-$wt_add;
                
                $conc_end = $part_conc;
                $conc_add = $part_conc_add;
                $conc_sub = $part_conc_rem;
                $conc_start = $conc_end +$conc_sub-$conc_add;

                $data['participiants'][] = array(   'col' => 'Active',
                                                    'count' => $part_act,
                                                    'percent' => 0);
                $data['participiants'][] = array(   'col' => 'Waitlist',
                                                    'count' => $part_wait,
                                                    'percent' => 0);
                $data['participiants'][] = array(   'col' => 'Concluded',
                                                    'count' => $part_conc,
                                                    'percent' => 0);
                
                $data['groups'] = $data_group['totals'];
                $data['group_count'] = $data_group['group_count'];

                $data['part_activ_det'][] = array(  'status' => 'Active',
                                                    'at_start' => $act_start,
                                                    'added' => $act_add,
                                                    'subtracted' => $act_sub,
                                                    'at_end' => $act_end );
                $data['part_activ_det'][] = array(  'status' => 'Waitlist',
                                                    'at_start' => $wt_start,
                                                    'added' => $wt_add,
                                                    'subtracted' => $wt_sub,
                                                    'at_end' => $wt_end );
                $data['part_activ_det'][] = array(  'status' => 'Concluded',
                                                    'at_start' => $conc_start,
                                                    'added' => $conc_add,
                                                    'subtracted' => $conc_sub,
                                                    'at_end' => $conc_end );
                
                $select = $db->select()
                         ->from('participantPrograms')
                         ->where('programID = ?', $typeID)
                         ->order('statusDate');
                $rows = $db->fetchAll($select);
                $arr_rows = array();
                foreach ($rows as $r) {
                    $tmp_id = $r['participantID'];
                    $tmp_st = $r['status'];
                    $tmp_dt = $r['statusDate'];
                    $arr_rows[$tmp_id][] = array( 'status' => $tmp_st,
                                                  'date' => $tmp_dt );
                }

                $arr_max = array();
                foreach ($arr_rows as $a => $val) {
                    $date_max = new DateTime('2000-01-01 00:00');
                    foreach ($val as $key) {
                        $date_tmp = new DateTime($key['date']);
                        if ($date_tmp > $date_max) { 
                            $date_max = $date_tmp; 
                            $rw = $key; 
                        }
                    }
                    $arr_last_part[$a] = $rw;
                }
                
                $today_act = new DateTime(date("Y-m-d"));
                $today_wait = new DateTime(date("Y-m-d"));
                foreach ($arr_last_part as $part => $val) {
                    $id = $part;
                    $date_tmp = new DateTime($val['date']);
                    switch ($val['status']) {
                        case 'active':
                            if ($date_tmp < $today_act) {
                                $today_act = $date_tmp;
                                $act_id = $id;
                            }
                            break;
                        case 'waitlist':
                            if ($date_tmp < $today_wait) {
                                $today_wait = $date_tmp;
                                $wait_id = $id;
                            }
                            break;
                    }
                }

                $today = new DateTime(date("Y-m-d"));
                $interval_act = $today->diff($today_act);
                $max_act_days = $interval_act->days;
                $interval_wait = $today->diff($today_wait);
                $max_wait_days = $interval_wait->days;

                $max_act_id = $act_id;
                $max_wait_id = $wait_id;

                if ($max_act_id != '') { 
                    $select = $db->select()
                                 ->from('participants')
                                 ->where('id = ?', $max_act_id);
                    $row = $db->fetchAll($select);
                    $row = $row[0];
                    $max_act_name = $row['firstName'].' '.$row['lastName'];
                }
                if ($max_wait_id != '') { 
                    $select = $db->select()
                                 ->from('participants')
                                 ->where('id = ?', $max_wait_id);
                    $row = $db->fetchAll($select);
                    $row = $row[0];
                    $max_wait_name = $row['firstName'].' '.$row['lastName'];
                }

                $data['part_outliers'][] = array(   'col' => 'Longest Waitlist',
                                                    'col_day' => $max_wait_days.' days',
                                                    'name'  => $max_wait_name );
                $data['part_outliers'][] = array(   'col' => 'Longest Active',
                                                    'col_day' => $max_act_days.' days',
                                                    'name'  => $max_act_name );

                $select = $db->select()
                             ->from('groups')
                             ->where('programID = ?', $typeID);
                $rows = $db->fetchAll($select);

                $Year = date('Y');
                $ytd = $Year.'-01-01';
                foreach ($rows as $row) {
                    $grp_tmp[] = array( 'name'=> $row['name'],
                                        'id' => $row['id'],
                                        'data' => $this->getGroupSummary($db, $beginTime, $row['id']),
                                        'data_ytd' => $this->getGroupSummary($db, $ytd, $row['id']) );
                }

                foreach ($grp_tmp as $grp) {
                    
                    $grp_d = $grp['data'];
                    $grp_d_t = $grp_d['count_grp_att'];
                    $grp_meet = $grp_d['count_grp'];
                    $grp_att = $grp_d_t;

                    $grp_d_ytd = $grp['data_ytd'];
                    $grp_d_t_ytd = $grp_d_ytd['count_grp_att'];
                    $grp_meet_ytd = $grp_d_ytd['count_grp'];
                    $grp_att_ytd = $grp_d_t_ytd;

                    if ($grp_att != 0) { $grp_avg_att = $grp_att/$grp_meet; }
                        else { $grp_avg_att = 0; }
                    if ($grp_att_ytd != 0) { $grp_avg_att_ytd = $grp_att_ytd/$grp_meet_ytd; }
                        else { $grp_avg_att_ytd = 0; }
                    
                    $data['group_active'][] = array('name' => $grp['name'],
                                                    'meetings' => $grp_meet,
                                                    'attendance' => $grp_att,
                                                    'avg_att' => number_format($grp_avg_att, 2, '.', ' '),
                                                    'ytd_att' => $grp_att_ytd,
                                                    'ytd_average' => number_format($grp_avg_att_ytd, 2, '.', ' ') );
                }
            } else { 
                $data = null; 
            }

            return $data;
        }

        protected function getGroupSummary($db, $begin_date, $id) {

            $select = $db->select()
                        ->from('groupMeetings')
                        ->order('date');
            $rows = $db->fetchAll($select);

            $date_begin = new DateTime($begin_date);
            foreach ($rows as $row) {
                if ($row['groupID'] == $id) {
                    $date_tmp = new DateTime($row['date']);
                    if ($date_tmp >= $date_begin) { $dt[] = $row; }
                }
            }
            $rows = $dt;
            $r_finale = 0;
            if ($rows != null) {
                foreach ($rows as $r) {
                    if ($r['enrolledIDs'] != null) {
                        $r_row = explode(',', $r['enrolledIDs']);
                        $r_count = count($r_row);
                        if ($r['unenrolledCount'] != null) {
                            $r_finale += $r_count +$r['unenrolledCount'];
                        } else {
                            $r_finale += $r_count;
                        }
                    }
                }
                $res['count_grp'] = count($rows);
                $res['count_grp_att'] = $r_finale;            
            } else {
                $res['count_grp'] = 0;
                $res['count_grp_att'] = 0;
            }
            
            return $res;
        }

        protected function getProgContent($title, $title_sub, $title_sub2, $data_all, $level, $beginTime) {
        
            if ($data_all != null) {   
            
                $w = 400; $h = 150;
                $base_url = 'https://chart.googleapis.com/chart';
                $rep_img_name = date('YmdHis').rand(0, 1000);
                $img = 'img/'.$rep_img_name;
                
                $names = '&chl=';
                $vals = '&chd=t:';
         
                $sum = 0;
                $data = $data_all['participiants'];
                foreach ($data as $row) { $sum += $row['count']; }
                $percent = 100/$sum;
                $content_col = '';
                $content_val = '';
                foreach ($data as $row) {
                    $vals .= $row['count'].',';
                    $names .= $row['col'].'%20'.ceil($row['count']*$percent).'%|';
                    $content_col .= $row['col'].'<br/>';
                    $content_val .= $row['count'].'<br/>';
                }
                $vals = substr($vals, 0, -1);
                $names = substr($names, 0, -1);
                $url = $base_url.'?cht=p3&chs='.$w.'x'.$h.$vals.$names;
                $request = file_get_contents($url);
                file_put_contents($img.'.png', $request);

                $rep_user_names = '';
                $rep_user_count = '';
                $groups = $data_all['groups'];
                foreach ($groups as $gr) {
                    $rep_user_names .= $gr['col'].'<br/>';
                    $rep_user_count .= $gr['val'].'<br/>';
                }
                $rep_count_grp = $data_all['group_count'];

                $html = '';
                $html .= '
                    <style rel="stylesheet" type="text/css">
                        #body { border: 2px solid; }
                        #footer { border: 1px solid; }
                        #bord { border-style: solid;
                                padding: 0px;
                                border-width: 1px; }
                    </style>
                    <div>
                        <table align="center" width="800">
                            <tr><td align="center" bgcolor="#FF8040">
                                <font color="#FFFFFF" face="sans-serif">
                                    <font size=6>'.$title.'<br/>
                                    <font size=5>'.$title_sub.'<br/>
                                    <font size=4>'.$title_sub2.'</font> 
                            </td></tr>
                            <tr valign="top">
                                <td><br/>
                                    <table><tr>
                                        <td valign="top" width="170px">
                                            <font face="sans-serif" size=5><strong>Total Participants</strong>
                                            <br/><br/><br/><br/><br/><br/>
                                            <font face="sans-serif" size=5><strong>Active Groups</strong>
                                        </td>
                                        <td valign="top" width="130px">
                                            <font face="sans-serif" size=4><br/><br/>'.$content_col
                                            .'<br/><br/><br/><br/><br/>'.$rep_user_names.'<br/>
                                        </td>
                                        <td valign="top" align="center" width="100px">
                                            <font face="sans-serif" size=4><strong>'.$sum.'</strong><br/><br/>'.$content_val.'
                                            <font face="sans-serif" size=5><strong><br/><br/>'.$rep_count_grp
                                            .'<br/><br/></strong>
                                            <font face="sans-serif" size=4>'.$rep_user_count  
                                        .'</td>
                                        <td width="380px" align="center"><font face="sans-serif" size=5>
                                            <strong>'.$title_sub.'<br/></strong><br/></font>
                                            <img src="'.$img.'.png"><br/>
                                        </td>
                                    </tr></table>
                                </td>
                            </tr>';
                if ($level != 'Summary') {

                    $html .= '<tr>
                                <td>
                                 <table width="800" border="0" align="center">
                                    <tr>
                                     <td width="400">
                                       <table>
                                        <tr>
                                            <td colspan="5" align="center" bgcolor="#FF8040" width="600" id="bord">
                                                <font face="sans-serif" color="#FFFFFF" size=3>Participant Activity Detail</font></td>
                                        </tr>
                                        <tr>
                                            <td id="bord" align="center" width="200"><font face="sans-serif" size=2>
                                                <strong>Status</td>
                                            <td id="bord" align="center" width="100"><font face="sans-serif" size=2>
                                                <strong># At Start</td>
                                            <td id="bord" align="center" width="100"><font face="sans-serif" size=2>
                                                <strong># Added</td>
                                            <td id="bord" align="center" width="100"><font face="sans-serif" size=2>
                                                <strong># Subtracted</td>
                                            <td id="bord" align="center" width="100"><font face="sans-serif" size=2>
                                                <strong># At End</td>
                                        </tr>';
                    
                    $part_activ_det = $data_all['part_activ_det'];
                    $i = 1;
                    foreach ($part_activ_det as $row) {
                        if (!(($i % 2) >0)) { $html .= '<tr>'; }
                            else { $html .= '<tr bgcolor="#C0C0C0">'; }
                        $i++;
                        $html .=    '<td>
                                        <font face="sans-serif" size=3><strong>'.$row['status'].'</td>
                                    <td align="center">
                                        <font face="sans-serif" size=3>'.$row['at_start'].'</td>
                                    <td align="center">
                                        <font face="sans-serif" size=3>'.$row['added'].'</td>
                                    <td align="center">
                                        <font face="sans-serif" size=3>'.$row['subtracted'].'</td>
                                    <td align="center">
                                        <font face="sans-serif" size=3>'.$row['at_end'].'</td></tr>';
                    }
                    $html .= '</table>
                          </td>
                          <td width="10"></td>
                          <td width="200">
                            <table>
                                <tr>
                                    <td colspan="2" align="center" bgcolor="#FF8040" width="400" id="bord">
                                        <font face="sans-serif" color="#FFFFFF" size=3>Participant Outliers</font>
                                    </td>
                                </tr>';

                    $part_outliers = $data_all['part_outliers'];
                    foreach ($part_outliers as $row) {
                        $html .= '<tr><td rowspan="2" id="bord" valign="top">
                                    <font face="sans-serif" size=3>'.$row['col'].'</td>
                                        <td>
                                            <font face="sans-serif" size=2><strong>'.$row['col_day'].'</strong>
                                        </td>
                                  </tr>
                                  <tr bgcolor="#C0C0C0">
                                    <td>
                                        <font face="sans-serif" size=2>'.$row['name']
                                    .'</td>
                                </tr>'; 
                    }            
                    $html .= '</table>
                          </td>
                        </tr>
                     </table>
                    </tr>
                    <tr><td><br/></td></tr>
                    <tr><td>
                        <table width="700">
                            <tr><td colspan="6" align="center" bgcolor="#FF8040" width="700" id="bord">
                                <font face="sans-serif" color="#FFFFFF" size=3>Group Activity Detail</font></td></tr>
                            <tr><td id="bord" align="center" width="200">
                                    <font face="sans-serif" size=2 ><strong>Group Name</td>
                                <td id="bord" align="center" width="150">
                                    <font face="sans-serif" size=2><strong># Meetings</td>
                                <td id="bord" align="center" width="150">
                                    <font face="sans-serif" size=2><strong># Attendance</td>
                                <td id="bord" align="center" width="150">
                                    <font face="sans-serif" size=2><strong>Avg Attendance</td>
                                <td id="bord" align="center" width="150">
                                    <font face="sans-serif" size=2><strong>YTD Attendance</td>
                                <td id="bord" align="center" width="150">
                                    <font face="sans-serif" size=2><strong>YTD Average</td>
                            </tr></tr>';
                    
                            $part_activ_group = $data_all['group_active'];
                            $i = 1;
                            foreach ($part_activ_group as $row) {
                                if (!(($i % 2) >0)) { $html .= '<tr>'; }
                                    else { $html .= '<tr bgcolor="#C0C0C0">'; }
                                $i++;
                                $html .= 
                                    '<td>
                                        <font face="sans-serif" size=3><strong>'.$row['name'].'
                                     </td>
                                     <td align="center">
                                        <font face="sans-serif" size=3>'.$row['meetings']
                                    .'</td>
                                    <td align="center">
                                        <font face="sans-serif" size=3>'.$row['attendance']
                                    .'</td>
                                     <td align="center">
                                        <font face="sans-serif" size=3>'.$row['avg_att']
                                    .'</td>
                                     <td align="center">
                                        <font face="sans-serif" size=3>'.$row['ytd_att']
                                    .'</td>
                                     <td align="center">
                                        <font face="sans-serif" size=3>'.$row['ytd_average']
                                    .'</td>
                                </tr>';
                            }
                    $html .= '</table>
                    </td></tr>';
                }        
                $html .= '</table></div>';
            } else { $html = 
                '<div>
                    <table align="center" width="800">
                        <tr><td align="center" bgcolor="#FF8040">
                            <font color="#FFFFFF" face="sans-serif">
                            <font size=6>'.$title.'<br/>
                            <font size=5>'.$title_sub.'<br/>
                            <font size=4>Weekly report, generated on '.$today.'</font>
                        </td></tr>
                        <tr>
                            <td><br/><strong>No participants associated with '.$title_sub.' - no data for this date range.</strong></td></tr>
                    </table>
                </div>'; }

            return $html;
        }

        protected function getGroupData($db, $beginTime, $id) {

            $select = $db->select()
                        ->from('groupMeetings')
                        ->order('date');
            $rows = $db->fetchAll($select);

            $date_begin = new DateTime($beginTime);
            foreach ($rows as $row) {
                if ($row['groupID'] == $id) {
                    $date_tmp = new DateTime($row['date']);
                    if ($date_tmp >= $date_begin) { $dt[] = $row; }
                        else { $dt_old[] = $row; }
                }
            }

            if ($dt_old != null) {
                foreach ($dt_old as $rw) {
                    $t_end = $rw['enrolledIDs'];
                    $t_uno = $rw['unenrolledCount'];
                }
            }
            $old_partip = 0;
            if ($t_end != null) {
                $old_partip += (count(explode( ',', $t_end ))) + $t_uno;
            } else {
                $old_partip += $t_uno;
            }

            $rows = $dt;
            $data['group_count'] = count($rows);
            
            $group_old = '';
            $part_sum = 0;
            $volunt_sum = 0;
            $guest_sum = 0;
            $vol_hours = 0;
            $old_grp = array();
            $test_grp = array();

            if (count($rows) >0) {

                foreach ($rows as $row) {
                    $count_unenrol = $row['unenrolledCount'];
                    $guest_sum += $count_unenrol;

                    $test_grp = explode(',', $row['enrolledIDs']);
                    if (($row['enrolledIDs'] != '')&($row['enrolledIDs'] != null)) {
                        $count_enrolID = count($test_grp);
                    } else { $count_enrolID = 0; }
                    $count_all = ($count_enrolID+$count_unenrol);
                    if (count($test_grp) > 0) {   
                        foreach ($test_grp as $grp) {
                            if ((!(in_array($grp, $old_grp)))&($grp!='')) { 
                                $old_grp[] = $grp; }
                        }
                    }
                    $volunt_sum += $row['volunteers'];
                    $vol_hours += $row['volunteers']*$row['duration'];

                    $date_str = DateTime::createFromFormat('Y-m-d', $row['date']);
                    $new_format = $date_str->format('d F');
                    $data['group'][] = array(   'col' => $date_str->format('F d'), 
                                                'count' => $count_all, 
                                                'notes' => $row['notes'],
                                                'date' => $row['date'] );
                }
                $part_sum = count($old_grp);
                $count_old = $old_partip;
                foreach ($data['group'] as $row) {
                    if ($count_old != null) { 
                        $saldo = $row['count']-$count_old; }
                    else { $saldo = 0; }
                    if ($saldo > 0) { $saldo = '+'.$saldo; }
                    $dt = DateTime::createFromFormat('Y-m-d', $row['date']);
                    $data['detail'][] = array(  'date' => $dt->format('F d, Y'),
                                                'count_part' => $row['count'],
                                                'saldo' => $saldo,
                                                'descript' => $row['notes'] );
                    $count_old = $row['count'];
                }
            } else { $data['group'] = null; $data['detail'] = null; }
            
            $data['totals'][] = array(  'col' => 'Meetings', 
                                        'val' => $data['group_count'] );
            $data['totals'][] = array(  'col' => 'Participants', 
                                        'val' => $part_sum );
            $data['totals'][] = array(  'col' => 'Volunteers', 
                                        'val' => $volunt_sum );
            $data['totals'][] = array(  'col' => 'Guests', 
                                        'val' => $guest_sum );
            $data['totals'][] = array(  'col' => 'Volunteer Hrs', 
                                        'val' => $vol_hours );
        
            return $data;
        }

        protected function getGroupContent($title, $title_sub, $title_sub2, $data_all, $level, $beginTime) {

            $rep_data_dates = '';
            $rep_group_dates = '';
            $rep_data_count = '';
            $rep_group_count = '';
            
            if ($data_all['group'] != null) {
                $w = 350; $h = 200;
                $base_url = 'https://chart.googleapis.com/chart';
                
                $rep_img_name = date('YmdHis').rand(0, 1000);
                $img = 'img/'.$rep_img_name;

                $names = '&chl=';
                $vals = '&chd=t:';

                $max = 0;
                $data = $data_all['group'];
                foreach ($data as $row) {
                    if ($max < $row['count']) { 
                        $max = $row['count']; 
                    }
                }
                
                $rel = ceil($max/10);
                $rel_max = $rel*10;
                
                $group_totals = $data_all['totals'];
                foreach ($group_totals as $row) {
                    $rep_group_names .= $row['col'].'<br/>';
                    $rep_group_count .= $row['val'].'<br/>';
                }
                $rep_count = $data_all['group_count'];
                foreach ($data as $row) {
                    $name_fin = str_replace(' ', '%20', $row['col']);
                    $vals .= ($row['count']).',';
                    $names .= $name_fin.'%20('.$row['count'].')|';
                    $rep_data_dates .= $row['col'].'<br/>';
                    $rep_data_count .= $row['count'].'<br/>';
                }
                $vals = substr($vals, 0, -1);
                $names = substr($names, 0, -1);
                $url = $base_url.'?cht=bvs&chxt=x,y&chxr=0,0,0|1,0,'.$rel_max.'&chds=0,'.$rel_max.'&chbh=40,30,30&chco=76A4FB&chls=2.0&chs='.$w.'x'.$h.$vals.$names;
                $request = file_get_contents($url);
                file_put_contents($img.'.png', $request);
            }
            $html = 
                '<style rel="stylesheet" type="text/css">
                     #body { border: 2px solid; }
                     #footer { border: 1px solid; }
                     #bord { border-style: solid;
                            padding: 0px;
                            border-width: 1px; }
                 </style>
                <div>
                    <table align="center" width="800">
                        <tr><td align="center" bgcolor="#FF8040">
                            <font color="#FFFFFF" face="sans-serif">
                                <font size=6>'.$title.'<br/>
                                <font size=5>'.$title_sub.'<br/>
                                <font size=4>'.$title_sub2.'</font> 
                        </td></tr>
                        <tr valign="top">
                            <td><br/>
                                <table>
                                <tr>
                                    <td valign="top" width="170px">
                                        <font face="sans-serif" size=4><strong>Group Meetings</strong></td>
                                    <td valign="top" width="130px"></td>
                                    <td valign="top" align="center" width="100px"><font size=4>
                                            <strong>'.$data_all['group_count'].'</strong></td>
                                    <td width="380px" align="center"><font face="sans-serif" size=5>
                                        <strong>Attendance in "'.$title_sub.'"</strong><br/></font></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td align="center"><font face="sans-serif" size=3>Date<br/>
                                        <strong>'.$rep_data_dates.'</strong><br/></td>
                                    <td align="center"><font face="sans-serif" size=3>Attendance<br/>
                                            <strong>'.$rep_data_count.'</strong></td>
                                    <td align="center" rowspan="3">';
                                        if ($rep_data_dates == '') { $html .= '<strong>NO IMAGE</strong>'; }
                                            else { $html .= '<img src="'.$img.'.png">'; }
                                    $html .= '</td>
                                </tr>
                                <tr>
                                    <td><font face="sans-serif" size=4><strong>Totals</strong></td>
                                    <td></td>
                                    <td align="center"></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td align="center"><font face="sans-serif" size=3><strong>'.$rep_group_names.'</strong><br/></td>
                                    <td align="center"><font face="sans-serif" size=3><strong>'.$rep_group_count.'</strong></td>
                                </tr>
                                </table>
                            </td>
                        </tr>';

            if (($level != 'Summary')&($data_all['detail'] != null)) {
                $html .= 
                    '<tr>
                        <td>
                            <div>
                                <font face="sans-serif" size=5>Group Meeting Details</font>
                            </div>
                            <font face="sans-serif" size=2><hr></font>';
                    $detail = $data_all['detail'];
                    foreach ($detail as $row) {
                        if ($row['descript'] == '') { $row['descript'] = '<i>No notes entered.</i><br/>'; }
                        $html .= 
                            '<table><tr>
                                <td width="350px" id="bord"><font face="sans-serif" size=3>
                                    <strong> '.$row['date'].'</strong></font></td>
                                <td width="200px" id="bord" align="center"><font face="sans-serif" size=3>
                                    <strong>'.$row['count_part'].' participants</strong></font></td>
                                <td width="300px" id="bord" align="center"><font face="sans-serif" size=3>
                                    <strong>'.$row['saldo'].' from last meeting</strong></font></td>
                                </tr></table>
                             <div><font face="sans-serif" size=3>
                             <strong>Notes:</strong> '.$row['descript'].'</font><br/><br/></div>
                             <font face="sans-serif" size=1><hr></font>';
                    }
                    $html .= '</td></tr>';
            }
            $html .= '</table></div>';

            return $html;
        }

        protected function getFormData($typeID, $db, $beginTime, $table, $type) {

            $data = array();
            $select = $db->select()
                         ->from($table)
                         ->where('responseDate = ?', $beginTime);
            $rows = $db->fetchAll($select);
            
            if (count($rows)>0) { 
                $total = count($rows);
            } else { 
                $total = 0; }
            
            if ($type != 'singleuse') {
                
                $select = $db->select()
                             ->from($table)
                             ->where('prePost = ?', 'pre')
                             ->where('responseDate >= ?', $beginTime);
                $rows = $db  ->fetchAll($select);

                if (count($rows)>0) { 
                    $pre = count($rows);
                } else { 
                    $pre = 0; }
                $select = $db->select()
                             ->from($table)
                             ->where('prePost = ?', 'post')
                             ->where('responseDate >= ?', $beginTime);
                $rows = $db->fetchAll($select);
                if (count($rows)>0) { $post = count($rows);
                } else { $post = 0; }
                $select = $db->select()
                             ->from($table)
                             ->where('prePost = ?', 'interim')
                             ->where('responseDate >= ?', $beginTime);
                $rows = $db->fetchAll($select);
                
                if (count($rows)>0) { 
                    $inter = count($rows);
                } else { 
                    $inter = 0; 
                }

            } else { 
                
                $pre = 'N/A'; 
                $post = 'N/A'; 
                $inter = 'N/A'; 
            }    
            
            $data['d'][] = array(   'col' => 'Pre', 
                                    'count' => $pre, 
                                    'percent' => 0);
            $data['d'][] = array(   'col' => 'Post', 
                                    'count' => $post, 
                                    'percent' => 0);
            $data['d'][] = array(   'col' => 'Interim', 
                                    'count' => $inter, 
                                    'percent' => 0);
            $data['total'] = $total;

            return $data;
        }

        protected function getFormContent($title, $title_sub, $title_sub2, $data_all, $level, $beginTime, $type) {

            if ($type != 'singleuse') {
            
                $w = 450; $h = 150;
                $base_url = 'https://chart.googleapis.com/chart';
                $rep_img_name = date('YmdHis').rand(0, 1000);
                $img = 'img/'.$rep_img_name;
                $html ='';
                
                $sum = 0;
                $total = $data_all['total'];
                $data = $data_all['d'];
                foreach ($data as $row) { 
                    $sum += $row['count']; 
                }
                if ($sum == 0) { 
                    $sum = 1; 
                }
                $percent = 100/$sum;
                $content_col = '';
                $content_val = '';

                $names = '&chl=';
                $vals = '&chd=t:';
                foreach ($data as $row) {
                    $vals .= $row['count'].',';
                    $names .= $row['col'].'%20'.$row['count']*$percent.'%|';
                    $content_col .= $row['col'].'<br/>';
                    $content_val .= $row['count'].'<br/>';
                }
                $vals = substr($vals, 0, -1);
                $names = substr($names, 0, -1);
                $url = $base_url.'?cht=p3&chs='.$w.'x'.$h.$vals.$names;
                $request = file_get_contents($url);
                file_put_contents($img.'.png', $request);
            
            } else {
                
                $content_col = '';
                $content_val = '';
                $data = $data_all['d'];
                foreach ($data as $row) {
                    $content_col .= $row['col'].'<br/>';
                    $content_val .= $row['count'].'<br/>';
                }
            }
            
            $html .=
                '<style rel="stylesheet" type="text/css">
                    #body { border: 2px solid; }
                    #footer { border: 1px solid; }
                 </style>
                <div>
                    <table align="center" width="800">
                        <tr>
                            <td align="center" bgcolor="#FF9866">
                                <font color="#FFFFFF" face="sans-serif">
                                <font size=7>'.$title.'<br/>
                                <font size=6>'.$title_sub.'<br/>
                                <font size=5>'.$title_sub2.'</font>
                            </td>
                        </tr>
                        <tr valign="top">
                            <td><br/>
                                <table>
                                    <tr>
                                        <td valign="top" width="150px">
                                            <font face="sans-serif" size=5><strong>Form Entries</strong></td>
                                        <td valign="top" width="80px">
                                            <font face="sans-serif" size=5><br/>
                                            <font face="sans-serif" size=4>Type<br/><strong>'.$content_col.'</strong></td>
                                        <td valign="top" align="right" width="80px">
                                            <font size=5><strong>'.$total.'</strong><br/>
                                            <font face="sans-serif" size=4>Number<br/><strong>'.$content_val.'</strong></td>
                                        <td width="480px" align="center"><font face="sans-serif" size=5>
                                            <strong>Form Entries for <br/>"'.$title_sub.'"</strong><br/><br/></font>';
                                            if (($total > 0)&($type != 'singleuse')) { 
                                                $html .= '<img src="'.$img.'.png">'; } 
                                            else { $html .= '<div>Count total entries = 0 <br/>Entries not found</div>'; }
                                $html .= '</td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>';

            return $html;
        }
        
        protected function getGroupTotal($arr_grp) {
            
            $g_count = 0;
            $meet = 0; $part = 0; $vol = 0; $guest = 0; $vol_h = 0; 
            foreach ($arr_grp as $grp) {
                $g_count++;
                $g_grp = array();
                $gg = $grp['totals'];
                $meet += $gg[0]['val'];
                $part += $gg[1]['val'];
                $vol += $gg[2]['val'];
                $guest += $gg[3]['val'];
                $vol_h += $gg[4]['val'];
            }
            $group['group_count'] = $g_count;
            $group['totals'][] = array( 'col' => 'Meetings', 
                                        'val' => $meet );
            $group['totals'][] = array( 'col' => 'Participants', 
                                        'val' => $part );
            $group['totals'][] = array( 'col' => 'Volunteers', 
                                        'val' => $vol );
            $group['totals'][] = array( 'col' => 'Guests', 
                                        'val' => $guest );
            $group['totals'][] = array( 'col' => 'Volunteer Hrs', 
                                        'val' => $vol_h );
            
            return $group;
        }

        public function getContent($rep_content, $mpdf) {

            foreach ($rep_content as $rep) {
                
                //set time range for report
                switch ($this->freq) {
                    case 'daily' : 
                        $beginTime = date("Y-m-d", strtotime("-1 days"));
                        $rep_title_freq = 'Daily ';
                        break;
                    case 'weekly': 
                        $beginTime = date("Y-m-d", strtotime("-7 days"));
                        $rep_title_freq = 'Weekly ';
                        break;
                    case 'monthly': 
                        $beginTime = date("Y-m-d", strtotime("-1 month"));
                        $rep_title_freq = 'Monthly ';
                        break;
                    default: 
                        throw new Exception("Faulty frequency $this->freq passed to stored report generator.");
                }

                $type = $rep['subtype'];
                $level = $rep['level'];
                $typeID = $rep['typeID'];
                $name = $rep['name'];
                print "*** check $level report ($name)  ...\n";
            
                $today = date('d M, Y');
                $title_sub2 = $rep_title_freq.'report, generated on '.$today;
                $cont = '';
                switch ($type) {
                    case 'prgs':
                        $title = "Program $level report";
                        $title_sub = $name;
                        print "*** get data ...\n";
                        $data_group = array();
                        $progs = new Application_Model_DbTable_ParticipantPrograms;
                        $db = $progs->getAdapter();
                        $select = $db->select()
                                     ->from('groups')
                                     ->where('programID = ?', $typeID);
                        $row = $db->fetchAll($select);
                        if (count($row)>1) {
                            foreach ($row as $r) { $data_group[] = $this->getGroupData($db, $beginTime, $r['id']); }
                        } else { 
                            $grp = $row[0];
                            $data_group[] = $this->getGroupData($db, $beginTime, $grp['id']);
                        }
                        $groups = $this->getGroupTotal($data_group);
                        $data = $this->getProgData($typeID, $db, $groups, $beginTime);
                        print "*** get content ...\n";
                        $cont = $this->getProgContent($title, $title_sub, $title_sub2, $data, $level, $beginTime, $typeID);
                        break;
                    case 'grps':
                        $title = "Group $level report";
                        $title_sub = $name;
                        print "*** get data ...\n";
                        $groups = new Application_Model_DbTable_GroupMeetings;
                        $db = $groups->getAdapter();
                        $data = $this->getGroupData($db, $beginTime, $typeID);
                        print "*** get content ...\n";
                        $cont = $this->getGroupContent($title, $title_sub, $title_sub2, $data, $level, $beginTime, $typeID);
                        break;
                    case 'forms':
                        $title = "Form $level Report";
                        $title_sub = $name;
                        print "*** get data ...\n";
                        $forms = new Application_Model_DbTable_Forms;
                        $form = $forms->getRecord($typeID);
                        $db = $forms->getAdapter();
                        $data = $this->getFormData($typeID, $db, $beginTime, $form['tableName'], $form['type']);
                        print "*** get content ...\n";
                        $cont = $this->getFormContent($title, $title_sub, $title_sub2, $data, $level, $beginTime, $typeID, $form['type']);
                        break;
                    default: throw new exception("Faulty data type $type passed to stored report generator.");
                }
                print "*** complete check $level report ($name) ***\n";
                
                $mpdf->AddPage();
                $mpdf->WriteHTML($cont);
                $mpdf->SetHTMLFooter('<div align="center"><font face="sans-serif" color="#808080" size=2><strong>
                    <hr>A Better Community Database - $agencyName</strong>
                    </font></div>');
            }

            return $mpdf;
        }
    }

 $reportsTable = new Application_Model_DbTable_StoredReports;
 $allReports = $reportsTable->fetchAll();
 
 foreach ($allReports as $record) {
    $report_name = $record['name'];
    $freq = $record['frequency'];
    $recips = $record['recipients'];
    $options = $record['includeOptions'];
    $enabled = $record['enabled'];
    $goToday = checkFrequency($freq);
    
    $html = array();
    if ($enabled && $goToday) {
        print "* working on '" .$record['name']. "' - start ...\n";
        $report = new StoredReport($recips, $options, $freq);
        $mpdf = new mPDF();
        $res = $report->getContent($report->content, $mpdf);
        $mpdf->Output('out/'.$report_name.'.pdf');
        
        // send mail
        
            print "*** Send mail ...\n";
            $arr_recip = $report->_processIDs($recips);
            $users = new Application_Model_DbTable_Users;
            foreach ($arr_recip as $row) {
                $user = $users->getRecord($row);
                $emls[] = array('adr'=>$user['eMail'],
                                'uname'=>$user['userName'] );
            }
            $today = date('d M, Y');
            $mail = new Zend_Mail();
            $mail->setBodyText('Please find your stored report attached.');
            $mail->setFrom('abcd@calgarydesign.net', 'ABCD');
            foreach ($emls as $us) {
                $mail->addTo($us['adr'], $us['uname']);
            }
            $mail->setSubject('ABCD:: '.$report_name.' report - '.$today);
            
            $content = file_get_contents('out/'.$report_name.'.pdf');
            $attachment = new Zend_Mime_Part($content);
            $attachment->type = 'application/pdf';
            $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $attachment->encoding = Zend_Mime::ENCODING_BASE64;
            $attachment->filename = $report_name.'.pdf'; // name of file

            $mail->addAttachment($attachment); 
            $mail->send();
        
        print "***** Complete! *****\n";
    } else {
        print "* working on '" .$record['name']. "' - not in schedule\n";
    }

 }

