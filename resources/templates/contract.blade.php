<h1>CONTRATO DE PRESTAÇÃO DE SERVIÇO</h1>

<p>
    Pelo presente instrumento particular de prestação de serviço, de um lado,
    <strong>{{ $contractant_name ?: 'Não informado' }}</strong>,
    {{ $contractant_nationality ?: 'não informado' }},
    {{ $contractant_marital_status ?: 'estado civil não informado' }},
    {{ $contractant_profession ?: 'profissão não informada' }},
    portador do RG nº <strong>{{ $contractant_rg ?: 'não informado' }}</strong>,
    inscrito no CPF nº <strong>{{ $contractant_cpf ?: 'não informado' }}</strong>,
    residente e domiciliado em
    <strong>{{ $contractant_address ?: 'endereço não informado' }}</strong>,
    na cidade de <strong>{{ $contractant_city_state ?: 'cidade não informada' }}</strong>,
    doravante denominado simplesmente <strong>CONTRATANTE</strong>; e
</p>

<p>
    de outro, <strong>{{ $contracted_name }}</strong>, pessoa jurídica de direito privado,
    inscrita no CNPJ nº <strong>{{ $contracted_cnpj ?: 'não informado' }}</strong>,
    com sede em <strong>{{ $contracted_address ?: 'endereço não informado' }}</strong>,
    na cidade de <strong>{{ $contracted_city_state ?: 'cidade não informada' }}</strong>,
    doravante denominada simplesmente <strong>CONTRATADA</strong>.
</p>

@if($is_minor)
    <p>
        O CONTRATANTE é menor de idade, identificado como
        <strong>{{ $minor_name ?: 'não informado' }}</strong>, nascido(a) em
        <strong>{{ $minor_birth_date ?: 'data não informada' }}</strong>, tendo como
        responsável legal <strong>{{ $legal_representative_name ?: 'não informado' }}</strong>,
        CPF nº <strong>{{ $legal_representative_document ?: 'não informado' }}</strong>.
    </p>
@endif

<p>
    Têm entre si, como justo e acordado, o que mutuamente se obrigam mediante as
    cláusulas abaixo transcritas:
</p>

<h2>DO OBJETO DO CONTRATO</h2>

<p>
    <strong>CLÁUSULA PRIMEIRA:</strong> Constitui objeto do presente contrato os serviços de
    academia, englobando a utilização de equipamentos e usufruindo dos serviços de instrução
    em atividade física oferecidos pela CONTRATADA, a contar da data de contratação,
    disponibilizando e reservando para o(a) CONTRATANTE horários e instrutores para a
    realização de sua atividade física.
</p>

<p>
    <strong>Parágrafo Primeiro:</strong> O presente contrato permite que o(a) CONTRATANTE pratique,
    dentro do estabelecimento da CONTRATADA, atividade física de acordo com os horários de
    sua conveniência e da disponibilidade da CONTRATADA, sendo ajustados horários fixos
    para as aulas do(a) CONTRATANTE.
</p>

<p>
    <strong>Parágrafo Segundo:</strong> A atividade física não englobada por este contrato será
    sempre motivo de aditivo contratual a ser ajustado pelas partes por escrito.
</p>

<h2>DOS PLANOS E VALORES</h2>

<p>
    <strong>CLÁUSULA SEGUNDA:</strong> Pelos serviços profissionais ora acordados, com base no
    plano <strong>{{ $plan_name ?: 'não informado' }}</strong>, o CONTRATANTE pagará à CONTRATADA
    o importe de <strong>{{ $plan_price }}</strong>, correspondente a
    <strong>{{ $plan_installments }}</strong> parcelas, com duração de
    <strong>{{ $plan_duration }}</strong>. O pagamento ocorrerá na forma estabelecida pelo
    plano escolhido, com início previsto para <strong>{{ $first_due_date ?: 'data não informada' }}</strong>.
</p>

<p>
    <strong>Parágrafo Primeiro:</strong> Os planos e valores aqui acordados possuem previsão
    expressa no Anexo I deste documento particular, ao qual o CONTRATANTE possui integral
    conhecimento, competindo a este a escolha do plano que melhor se adeque à sua realidade.
</p>

<p>
    <strong>Parágrafo Segundo:</strong> A previsão expressa dos planos será informada antes da
    contratação, podendo o CONTRATANTE mudar a escolha do plano, desde que previamente
    informado à CONTRATADA e que o plano tenha sido findado ou não tenha iniciado.
</p>

<p>
    <strong>Parágrafo Terceiro:</strong> O inadimplemento de qualquer dos valores estipulados
    no caput desta cláusula importará no vencimento antecipado das demais parcelas, além da
    incidência de juros de 0,2% ao dia e multa de 20% sobre o valor deste contrato.
</p>

<h2>DO LOCAL DA PRESTAÇÃO DE SERVIÇO</h2>

<p>
    <strong>CLÁUSULA TERCEIRA:</strong> A prestação de serviços será realizada em
    <strong>{{ $academy_address ?: 'endereço da academia não informado' }}</strong>.
</p>

<h2>HORÁRIO DE FUNCIONAMENTO E AGENDAMENTOS</h2>

<p>
    <strong>CLÁUSULA QUARTA:</strong> O horário de funcionamento da CONTRATADA será das
    <strong>{{ $opening_hours ?: 'não informado' }}</strong>. O CONTRATANTE somente terá acesso
    aos serviços da academia em horário agendado, a fim de evitar superlotação e garantir o
    melhor atendimento. Fica acordado que o horário de
    <strong>{{ $scheduled_time ?: 'não informado' }}</strong> será o período em que o(a)
    CONTRATANTE poderá usufruir dos serviços disponibilizados pela academia.
</p>

<p>
    <strong>Parágrafo Primeiro:</strong> Será permitida, pela CONTRATADA, a troca de horários de
    atendimento ao CONTRATANTE, desde que este informe previamente, com antecedência mínima
    de 48 horas, e que haja disponibilidade para realizar o atendimento.
</p>

<p>
    <strong>Parágrafo Segundo:</strong> Caso haja prejuízo aos demais alunos pela modificação
    do horário de atendimento do CONTRATANTE, mesmo que este tenha avisado com antecedência,
    o pedido de modificação poderá ser negado, não podendo o CONTRATANTE se opor a essa
    negativa, em vista da boa-fé da CONTRATADA para com os demais alunos.
</p>

<h2>DAS REPOSIÇÕES</h2>

<p>
    <strong>CLÁUSULA QUINTA:</strong> A perda de qualquer aula por culpa do CONTRATANTE não será
    reposta, uma vez que compete a este comparecer para as aulas contratadas dentro do
    horário por ele acordado.
</p>

<p>
    <strong>Parágrafo Primeiro:</strong> Eventual atraso por parte do CONTRATANTE não será
    reposto, podendo este realizar a aula durante o período faltante, excluídos os minutos de
    atraso, desde que o atraso não tenha ultrapassado a duração total da aula. Caso isso
    ocorra, haverá a perda da aula por parte do CONTRATANTE, que não será reposta.
</p>

<p>
    <strong>Parágrafo Segundo:</strong> Caso a perda da aula se dê por culpa ou responsabilidade
    da CONTRATADA, compromete-se a CONTRATADA a realizar a reposição de forma obrigatória,
    adequando os horários de funcionamento para possibilitar a reposição.
</p>

<h2>DO PRAZO</h2>

<p>
    <strong>CLÁUSULA SEXTA:</strong> O presente Contrato de Prestação de Serviço terá duração de
    <strong>{{ $contract_duration }}</strong>, a contar da data de sua contratação. Este termo
    renova-se automaticamente caso não seja contestado por qualquer das partes.
</p>

<h2>DO USO DE IMAGEM</h2>

<p>
    <strong>CLÁUSULA SÉTIMA:</strong> A parte CONTRATANTE poderá ceder seu direito de imagem
    para a CONTRATADA, de forma gratuita, com o objetivo de promover sua publicidade, mediante
    termo de consentimento expressamente assinado pelo CONTRATANTE.
</p>

<p>
    <strong>Parágrafo Primeiro:</strong> O uso da imagem possui caráter opcional pelo CONTRATANTE,
    não podendo este ser compelido a ceder sua imagem sem sua vontade.
</p>

<p>
    <strong>Parágrafo Segundo:</strong> Com a assinatura do termo de consentimento, o CONTRATANTE
    se obriga a ceder seu direito de imagem para fins de publicidade da CONTRATADA, não podendo
    nada ser reclamado posteriormente, em vista da expressa anuência da parte contratante.
</p>

<p>
    <strong>Parágrafo Terceiro:</strong> O consentimento vigorará pelo período de vigência do
    contrato. Eventuais propagandas já realizadas com a imagem do CONTRATANTE poderão ser
    publicadas posteriormente, desde que a publicidade e o uso da imagem tenham ocorrido durante
    a vigência do presente contrato.
</p>

<h2>DAS OBRIGAÇÕES DA CONTRATADA</h2>

<p><strong>CLÁUSULA OITAVA:</strong> A CONTRATADA se obriga a:</p>

<ol type="a">
    <li>Prestar os serviços profissionais com estrita observância dos termos e condições prescritos no presente contrato e na lei.</li>
    <li>Disponibilizar ao CONTRATANTE acesso à sua sede física, aos seus equipamentos e ao serviço de seu instrutor dentro do prazo acordado na Cláusula Quarta.</li>
    <li>Garantir ao CONTRATANTE o melhor atendimento e experiência profissional que a CONTRATADA puder dispor.</li>
    <li>Disponibilizar meios facilitados de comunicação e demais formas de contato.</li>
    <li>Repor as aulas eventualmente perdidas por culpa da CONTRATADA.</li>
    <li>Prestar todas as informações e auxílios necessários para entender as cláusulas descritas no presente contrato.</li>
    <li>Atender às solicitações do CONTRATANTE sempre que for possível, dentro dos parâmetros estipulados neste contrato, na lei e nos preceitos éticos-profissionais.</li>
</ol>

<h2>DAS OBRIGAÇÕES DO/DA CONTRATANTE</h2>

<p><strong>CLÁUSULA NONA:</strong> O(A) CONTRATANTE se obriga a:</p>

<ol type="a">
    <li>Respeitar todas as estipulações e obrigações contraídas neste contrato e na lei, bem como os parâmetros da boa-fé.</li>
    <li>Dar a título de contraprestação os valores estipulados na Cláusula Segunda, em seu exato termo, sem atraso.</li>
    <li>Escolher de forma adequada o plano de treinamento descrito no Anexo I.</li>
    <li>Avisar, com antecedência mínima de 48 horas, os dias em que não poderá comparecer no horário estipulado.</li>
    <li>Não cobrar da CONTRATADA por reposições de aulas perdidas por culpa do CONTRATANTE.</li>
    <li>Respeitar o termo de vigência deste documento e avisar, com antecedência e por escrito, que não deseja a renovação.</li>
    <li>Informar à CONTRATADA eventual mudança de endereço e meios de contato.</li>
</ol>

<h2>DA DENÚNCIA E RESCISÃO CONTRATUAL</h2>

<p>
    <strong>CLÁUSULA DÉCIMA:</strong> Este contrato poderá ser renunciado por qualquer das partes
    a qualquer tempo, bastando prévia notificação por escrito, com antecedência mínima de 30
    dias, período durante o qual as partes acertarão todas as eventuais pendências decorrentes
    do contrato.
</p>

<p>
    <strong>Parágrafo Único:</strong> No decorrer do período de 30 dias, as partes acertarão todas as
    eventuais pendências decorrentes do contrato, devendo o(a) CONTRATANTE pagar integralmente
    à CONTRATADA os serviços prestados durante esse período, bem como a multa pela rescisão
    antecipada correspondente a 50% sobre o valor deste instrumento particular.
</p>

<h2>DA CESSÃO CONTRATUAL</h2>

<p>
    <strong>CLÁUSULA DÉCIMA PRIMEIRA:</strong> O(A) CONTRATANTE poderá ceder a terceiro, de forma
    gratuita, desde que mutuamente acordado e mediante autorização da empresa CONTRATADA, os
    direitos e obrigações contraídos neste documento, respeitando as demais cláusulas contratuais.
</p>

<p>
    <strong>Parágrafo Primeiro:</strong> A cessão somente será possível mediante concordância da
    CONTRATADA, sendo imprescindível a realização de novo contrato acessório específico.
</p>

<p>
    <strong>Parágrafo Segundo:</strong> Na relação entre CONTRATANTE (cedente) e CONTRATADA
    (cedido), ocorrendo a transmissão da relação contratual a terceiro, extinguem-se
    subjetivamente os direitos e obrigações contratuais entre eles. Caso não sejam cumpridas as
    obrigações pelo terceiro, a CONTRATADA poderá demandar o cumprimento pelo CONTRATANTE, que
    permanece vinculado às disposições contratuais.
</p>

<h2>DAS DISPOSIÇÕES GERAIS</h2>

<p>
    <strong>CLÁUSULA DÉCIMA SEGUNDA:</strong> Os casos omissos neste contrato serão dirimidos
    à luz da legislação e dos usos e costumes em vigor.
</p>

<p>
    <strong>CLÁUSULA DÉCIMA TERCEIRA:</strong> Eventuais dúvidas do CONTRATANTE sobre este
    instrumento particular serão dirimidas pela CONTRATADA.
</p>

<p>
    <strong>CLÁUSULA DÉCIMA QUARTA:</strong> Antes de eventual processo, as partes comprometem-se
    a entrar em contato uma com a outra com o objetivo de resolver extrajudicialmente
    qualquer lide.
</p>

<p>
    <strong>CLÁUSULA DÉCIMA QUINTA:</strong> As partes elegem o foro de
    <strong>{{ $forum_city ?: 'cidade não informada' }}</strong>, estado de
    <strong>{{ $forum_state ?: 'não informado' }}</strong>, com renúncia expressa a qualquer
    outro, para dirimir dúvidas quanto à execução deste contrato.
</p>

<h2>IDENTIFICAÇÃO DAS PARTES</h2>

<p>
    Data de emissão: <strong>{{ $contract_date }}</strong>.
</p>

<p>
    <strong>CONTRATANTE:</strong>
    {{ $contractant_name ?: 'Não informado' }} —
    CPF: <strong>{{ $contractant_cpf ?: 'Não informado' }}</strong>
</p>

@if($is_minor)
    <p>
        <strong>RESPONSÁVEL LEGAL:</strong>
        {{ $legal_representative_name ?: 'Não informado' }} —
        CPF: <strong>{{ $legal_representative_document ?: 'Não informado' }}</strong>
    </p>
@endif

<p>
    <strong>CONTRATADA:</strong>
    {{ $contracted_name }}
</p>
