package com.esap_be.demo.ScheduledJobs;

import com.esap_be.demo.Controllers.ISOController;
import com.esap_be.demo.Models.Entities.ISOCode;
import com.esap_be.demo.Models.Entities.ISOCodeDetails;
import com.esap_be.demo.Repositories.IIsoRepository;
import com.esap_be.demo.Services.CustomISOCodeDetailsDeserializer;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.module.SimpleModule;
import org.quartz.JobExecutionContext;
import org.quartz.JobExecutionException;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.scheduling.quartz.QuartzJobBean;
import org.springframework.web.client.RestTemplate;

import java.util.Date;
import java.util.List;
import java.util.Map;

public class ISOCodesJob extends QuartzJobBean {

    private static final Logger logger = LoggerFactory.getLogger(ISOController.class);

    private final IIsoRepository isoRepository;

    private final String getAllISOCodesURL = "https://iso3166-updates.com/api/all";

    public ISOCodesJob(IIsoRepository isoRepository) {
        this.isoRepository = isoRepository;
    }

    @Override
    protected void executeInternal(JobExecutionContext jobExecutionContext) throws JobExecutionException {
        logger.info("SchedulerJob::FetchAndSaveISOCodes - Start at {}", new Date());
        RestTemplate restTemplate = new RestTemplate();
        ResponseEntity<String> response = restTemplate.getForEntity(getAllISOCodesURL, String.class);

        if (response.getStatusCode() == HttpStatus.OK && response.getBody() != null) {
            logger.info("ISO codes fetched successfully.");
            ObjectMapper objectMapper = new ObjectMapper();

            SimpleModule module = new SimpleModule();
            module.addDeserializer(Map.class, new CustomISOCodeDetailsDeserializer());
            objectMapper.registerModule(module);

            try {
                Map<String, List<ISOCodeDetails>> isoDataMap =
                        objectMapper.readValue(response.getBody(), new TypeReference<Map<String, List<ISOCodeDetails>>>() {});

                for (Map.Entry<String, List<ISOCodeDetails>> entry : isoDataMap.entrySet()) {
                    ISOCode isoCode = new ISOCode(entry.getKey(), entry.getValue());
                    isoRepository.save(isoCode);
                }
                logger.info("SchedulerJob::FetchAndSaveISOCodes - End at {}", new Date());
            } catch (JsonProcessingException e) {
                logger.error("Failed to deserialize ISO codes.", e);
            }
        } else {
            logger.error("Failed to fetch ISO codes.");
        }

    }
}
