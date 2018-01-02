<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">


            <table>

                <tr>
                    <th>Type</th>
                    <th>Value</th>
                </tr>

                @foreach($tokens as $token)
                    <tr>
                        <td>
                            @if( $token['type']  == 'id')
                                <span class="badge badge-primary">{{ $token['type'] }}</span>
                            @elseif( $token['type']  == 'keyword')
                                <span class="badge badge-info">{{ $token['type'] }}</span>
                            @elseif($token['type']  == 'delim2')
                                <span class="badge badge-warning">{{ $token['type'] }}</span>
                            @elseif($token['type']  == 'literal')
                                <span class="badge badge-secondary">{{ $token['type'] }}</span>
                            @elseif($token['type']  == 'comment')
                                <span class="badge badge-success">{{ $token['type'] }}</span>
                            @else
                                <span class="badge badge-danger">{{ $token['type'] }}</span>
                            @endif
                        </td>
                        <td>{{ $token['value'] }}</td>
                    </tr>
                @endforeach
            </table>

