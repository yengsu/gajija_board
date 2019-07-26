//http://www.bsidesoft.com/?p=321
function isLeapYear( $year ){
    return ( $year % 4 == 0 && $year % 100 != 0 ) || $year % 400 == 0;
}
function dateDiff( $interval, $dateOld, $dateNew ){
    var date1, date2, d1_year, d1_month, d1_date, d2_year, d2_month, d2_date, result, temp, i;
    date1 = dateGet( $dateOld );
    date2 = dateGet( $dateNew );
 
    switch( $interval.toLowerCase()){
    case'y': //year
        return date2.getFullYear() - date1.getFullYear();
    case'm': //month
        return ( date2.getFullYear() - date1.getFullYear() ) * 12 + date2.getMonth() - date1.getMonth();
    case'h': //hour
        return parseInt( ( date2.getTime() - date1.getTime() ) / 3600000 );
    case'i': //minute
        return parseInt( ( date2.getTime() - date1.getTime() ) / 60000 );
    case's': //second
        return parseInt( ( date2.getTime() - date1.getTime() ) / 1000 );
    case'ms': //msecond
        return date2.getTime() - date1.getTime();
    case'd': //day
        d1_year = date1.getFullYear();
        d1_month = date1.getMonth();
        d1_date = date1.getDate();
 
        d2_year = date2.getFullYear();
        d2_month = date2.getMonth();
        d2_date = date2.getDate();
 
        result = 0;
 
        if( d2_year - d1_year > 0 ){
 
            // 연도가 다른 경우 3단계 처리
            result += dateDiff( 'd', dateGet( date1 ), dateGet( d1_year + '-12-31' ) );
            result += dateDiff( 'd', dateGet( d2_year + '-1-1' ), dateGet( date2 ) );
            for( i = d1_year + 2 ; i < d2_year ; i++ ) result += 365 + ( isLeapYear( i ) ? 1 : 0 );
 
        }else{
 
            temp = [31,28,31,30,31,30,31,31,30,31,30,31];
            if ( isLeapYear( d1_year ) ) temp[1]++;
 
            if( d2_month - d1_month > 0 ){
 
                // 월이 다른 경우 3단계 처리
                result += temp[d1_month] - d1_date + 1;
                result += d2_date - 1;
                for( i = d1_month + 1 ; i < d2_month ; i++ ) result += temp[i];
 
            }else{
                result += d2_date - d1_date;
            }
        }
        return result * order;
    default:
        return null;
    }
}
function dateGet( $date ){
 
    var i, temp, h, m, s, ms;
 
    switch( typeof( $date ) ){
    case'string' :
        ms = h = m = s = 0;
 
        // 1
        $date = $date.split( '-' );
 
        // 2
        if( $date[2].indexOf( ' ' ) > -1 ){
            temp = $date[2].split( ' ' );
            $date[2] = temp[0];
 
            //3
            if( temp.length == 3 ){
                ms = parseInt( temp[2], 10 );
                if( ms > 1000 ) ms = 999;
            }
 
            //4, 5
            temp = temp[1].split( ':' );
            h = parseInt( temp[0], 10 );
            m = parseInt( temp[1], 10 );
            s = parseInt( temp[2], 10 );
        }
 
        //6, 7
        return new Date(
            parseInt( $date[0], 10 ),
            parseInt( $date[1], 10 ) - 1,
            parseInt( $date[2], 10 ),
            h, m, s, ms
        );
 
    //8
    case'number':
        return new Date( $date );
 
    //9
    default:
        if( $date.constructor == Date ){
            return $date;
        }else{
            return new Date();
        }
    }
}